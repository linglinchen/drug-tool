<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//ES//DTD procedures DTD version 1.0//EN//XML" "c:\temp\delete\Consult\Procedures\procedure_0_1.dtd">

<xsl:stylesheet version="3.0"
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
		
		xmlns:dct="http://purl.org/dc/terms/"
		xmlns:ecm="http://www.elsevier.com/xml/schema/rdf/common/Metadata-1/"
		xmlns:ecroles="http://www.elsevier.com/xml/xhtml/common/Roles-1/"
		xmlns:eck="http://www.elsevier.com/xml/schema/rdf/project/Clinical-Key-1/"
		xmlns:xs="http://www.w3.org/2001/XMLSchema"
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
		xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
		xmlns:prism="http://prismstandard.org/namespaces/basic/2.0/"
		xmlns:pav="http://purl.org/swan/pav/provenance/"
		xmlns:xlink="http://www.w3.org/1999/xlink"
		xmlns="http://www.w3.org/1999/xhtml"
		xmlns:xhtml="http://www.w3.org/1999/xhtml"
		
		
		exclude-result-prefixes="xhtml">
	
<!--
	applies changes that had been performed manually (ie, converts 2018 to 2019 Procedure Videos)

	2019-08-12 JWS	original; will calculate entity_id and preserve any that were already assigned
	2019-08-19 JWS	removed checklists per BPDT-2794
	2019-10-28 JWS	post-procedure structure fixer missed when selecting using h2s due to lack of namespace

	0. be sure your XSL transformation tool supports XSLT 3
	1. replace the source XML's DOCTYPE with the DOCTYPE from this XSLT. This removes the unfollowable reference to the old DTD and adds support for known entities
		a. life will also be easier if you combine all XML files into a single file
	2. clean up source XML
		a. remove all but default namespaces from ground, leave only on html
		b. add new named entities to DOCTYPE to be replaced with characters (eg &ndash; is en dash: –); decimal entities do not require this
		c. fix well-formedness errors
		d. fix other non-standard, unwanted characters (eg, line separator) and add any additional content needed (eg, tables). UUID stability is dependent on the number of characters
	3. transform the source XML against this XSLT
		a. note comment below which is 2019 edition transformation; rules above will largely preserve original (2018 edition) but below applies what had been manual changes
		b. note setting of $devmode param
		c. note settings of $existing_entityid_file (et al), $uuid_* params and $entity_id_base; recommend changing $uuid_* to new random values for a new title to help ensure uniqueness
			REQUIRED: Each dtd requires changes to: (KEYS: ckid) (TEMPLATES: get_entity_id, generateUUID:uniquenode) (VARIABLES: entity_id_base, uuid_tree:atoms) (OTHER: how the ID assignment is concatted)
			REQUIRED: Each product requires changes to (PARAMS: uuid_multiplier, uuid_increment, uuid_divisor, existing_entityid_file) 
		d. verify output does not contain ERROR text (eg, para[@type=special-item]/@role)
		e. restore namespaces to ground (for ingestion) and the default namespace to all html nodes (for METIS)
	4. save transformed XML in a batch directory (batch_YYYY-MM-DD) following this naming convention full_book.xml
		a. the main Procedures DTD needs to be followable to ensure the output is valid. It can be changed in this XSLT
	5. ZIP batch directory and upload to converted_XML bucket in project's bucket on S3
-->

<xsl:output method="xml" encoding="utf-8" indent="yes"
 omit-xml-declaration="yes"
 doctype-public="-//ES//DTD procedures DTD version 1.0//EN//XML" doctype-system="https://major-tool-development.s3.amazonaws.com/DTDs/procedure_0_1.dtd"
 media-type="text/html"/>

<xsl:preserve-space elements="br"/>

<xsl:param name="devmode" select="'N'"/>	<!-- set to Y to: 1) skip counting characters for $entity_id_base 2) generate only 10 UUIDs 3) avoid the expense of calculating the def/@n -->
<xsl:param name="output_tree" select="'false'"/> <!-- set to true to output the $uuid_tree fragment used for key generation when ALSO in devmode=Y -->
<!-- used to calculate the UUIDs, use the same values for each transformation of a single title to ensure the same values are calculated each time; recommend changing this for new titles -->
	<xsl:param name="uuid_multiplier" select="76600"/>
	<xsl:param name="uuid_increment" select="45"/>
	<xsl:param name="uuid_divisor" select="76364"/>

<xsl:param name="existing_entityid_file" select="'c:\temp\delete\Consult\Procedures\entityid.xml'"/> <!-- the location of the file containing the entity_ids already assigned in METIS -->

<xsl:variable name="entityids" select="document($existing_entityid_file)" as="document-node()"/>

<!-- unique identifier for previously assigned entity_ids -->
<xsl:key name="ckid" match="//procedure" use="@ckid"/>

<!-- find assigned entity_id in $entityids -->
<xsl:template name="get_entity_id">
	<xsl:variable name="temp" select="parent::*/xhtml:head/xhtml:base/@href"/>
	<xsl:value-of select="$entityids/key('ckid',$temp)/@entityid"/>
</xsl:template>



<!-- used both as a prefix and to seed the calculation of UUIDs, this counts the number of characters in a document and will result in repeatable UUID generation on a single document yet be fairly unique if not used frequently
	NOTE: outside devmode, if the document text changes length by even one character, UUIDs will not be consistent across transformations so fix characters and content *before* relying on the UUIDs -->
<xsl:variable name="entity_id_base">
	<xsl:choose>
		<!-- CAUTION: for speed and simplicity, devmode will use an arbitrary number to force UUIDs to be the same while during testing and modifying XML, final output UUIDs will be different than during devmode -->
		<xsl:when test="$devmode='Y'">5993212</xsl:when>
		<xsl:otherwise><xsl:value-of select="string-length(*)"/></xsl:otherwise>
	</xsl:choose>
</xsl:variable>

<!-- seeds a document tree with UUIDs paired to unique IDs for each atomic element, which is then used in $uuid_key and $uuid_uniquenode -->
<xsl:variable name="uuid_tree">
	<uuids>
		<xsl:call-template name="generateUUID">
			<xsl:with-param name="seed" select="$entity_id_base"/>
			<xsl:with-param name="atoms" select="//xhtml:html"/>
			<xsl:with-param name="counter" select="1"/>
		</xsl:call-template>
	</uuids>
</xsl:variable>

<!-- keys used to improve the efficiency of applying UUIDs to atoms -->
<!-- NOTE: if document has a default namespace, this will have to use it -->
<xsl:key name="uuid_key" match="$uuid_tree//xhtml:atom | $uuid_tree//atom" use="@id"/>
<xsl:key name="uuid_uniquenode" match="$uuid_tree//xhtml:atom | $uuid_tree//atom" use="@uniquenode"/>


<!-- populates $uuid_tree with a pseudo-UUID from a seed, based on the Linear Congruential Generator: Xn+1 = (aXn + c) mod c -->
<!-- $atoms are the set of elements in source needing UUIDs once transformed into atoms -->
<xsl:template name="generateUUID">
	<!-- This seed begins as $entity_id_base, but once generateUUID is recursing it becomes the previous atom's UUID -->
	<xsl:param name="seed"/>
	<!-- Select the unique nodes in source that will require a UUID upon transformation -->
	<xsl:param name="atoms"/>
	<xsl:param name="counter"/><!-- USED OF LIMIT EXPENSE OF KEY CALCULATION when $devmode=Y -->
	
	<!--($a * $seed + $c) mod $m-->
	<xsl:variable name="uuid" select="($uuid_multiplier * $seed + $uuid_increment) mod $uuid_divisor"/>
	
	<!-- first atom in $atoms -->
	<xsl:element name="atom">
		<xsl:attribute name="uuid" select="format-number($uuid, '999999')"/>
		<xsl:attribute name="genid" select="generate-id($atoms[position()=1])"/>
		<xsl:attribute name="uniquenode" select="$atoms[position()=1]/xhtml:head/xhtml:base/@href"/>	<!-- a distinct per atom, omnipresent node to test pairing source atoms with generated IDs -->
	</xsl:element>
	<!-- recurses through all of the $atoms so the current seed can be used to calculate the next UUID -->
	<!-- $counter combined with $devmode=Y will result in only calculating 10 UUIDs -->
	<xsl:if test="($devmode != 'Y' or $counter &lt; 10) and count($atoms) &gt; 1">
	<!--<xsl:if test="count($atoms) &gt; 1">-->
		<xsl:call-template name="generateUUID">
			<xsl:with-param name="seed" select="$uuid"/>
			<xsl:with-param name="atoms" select="$atoms[not(position()=1)]"/>
			<xsl:with-param name="counter" select="$counter + 1"/><!-- KEY creation limiter -->
		</xsl:call-template>
	</xsl:if>
	
</xsl:template>



	<xsl:variable name="newline">
		<xsl:text>&#xa;</xsl:text>
	</xsl:variable>





<!-- identity rules for everything else; comments are retained because of alert.gif, METIS will handle them -->
<!-- prevent copying of namespaces -->
<xsl:template match="@*|node()|comment()">
	<xsl:copy copy-namespaces="no">
		<xsl:apply-templates select="@*|node()|comment()"/>
	</xsl:copy>
</xsl:template>



<xsl:template match="xhtml:ground | ground">
	<!-- document the random strings used to generate output UUIDs -->
	<xsl:comment> uuid_multiplier: <xsl:value-of select="$uuid_multiplier"/> </xsl:comment><xsl:value-of select="$newline"/>
	<xsl:comment> uuid_increment: <xsl:value-of select="$uuid_increment"/> </xsl:comment><xsl:value-of select="$newline"/>
	<xsl:comment> uuid_divisor: <xsl:value-of select="$uuid_divisor"/> </xsl:comment><xsl:value-of select="$newline"/>
	<xsl:value-of select="$newline"/>

	<!-- outputs contents of tree fragment used for key generation when $devmode=Y and $output_tree=true-->
	<xsl:if test="$devmode='Y' and $output_tree='true'">
		<xsl:copy-of select="$uuid_tree"/>
	</xsl:if>
	
	<xsl:copy copy-namespaces="no">
		<xsl:apply-templates select="@*|node()|comment()"/>
	</xsl:copy>
</xsl:template>


<xsl:template match="xhtml:html | html">
	<html>
		<xsl:copy-of select="namespace::*"/>
		<!-- ensures @id is in output; Emergency Medicine lacked these -->
		<xsl:attribute name="id">
			<xsl:call-template name="set_html_id"/>
		</xsl:attribute>
		<xsl:apply-templates select="@*"/>

		<xsl:apply-templates/>
	</html>
</xsl:template>

<!-- pick up already-assigned entity_id or generate a new one -->
<xsl:template match="@id[parent::xhtml:html or parent::html]" name="set_html_id">
	<xsl:variable name="entity_id">
		<xsl:call-template name="get_entity_id"/>
	</xsl:variable>
	<!-- when called by name the context will be the html node rather than @id; this adapts for both situations -->
	<xsl:variable name="find_id" select="current()/parent::*/xhtml:head/xhtml:base/@href | current()/xhtml:head/xhtml:base/@href"/>

	<xsl:attribute name="id">
		<!-- XML node ID prefix for use in both outputs -->
		<xsl:text>html.</xsl:text>

		<xsl:choose>
			<!-- useful whenever you want to preseve entity IDs from previously ingested XML; pull from db and put into external XML file --> 
			<xsl:when test="$entity_id != ''">
				<xsl:value-of select="$entity_id"/>
			</xsl:when>
			<!-- typical ID generation: XML node ID prefix + unique ID base for document + generated-id for atom + output of UUID process on atom -->
			<xsl:otherwise>
				<xsl:value-of select="concat('', format-number($entity_id_base, '9999999'), translate($uuid_tree/key('uuid_uniquenode', $find_id)[position()=1]/@genid, 'ghijklmnopqrstuvwxyz', '0123456789abcdef0123'), $uuid_tree/key('uuid_uniquenode', $find_id)[position()=1]/@uuid)"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:attribute>
</xsl:template>



<!-- below are the rules required only for the 2019 edition; everything above is used for both 2018 and 2019 -->

<!-- omit these nodes -->
<xsl:template match="
 eck:homunculus
 | eck:specialty[ancestor::rdf:Description/eck:mainSpecialty[@rdf:resource = current()/@rdf:resource]]
 | ecm:hasKeyword[descendant::ecm:keywordType/@rdf:resource[.='ectypes:taxonomies/CPT' or .='ectypes:taxonomies/ICD9']]
 | ecm:hasSubCategory
 | (xhtml:h1 | h1)[@class='section-title'][parent::*[@class='section_fulldetails']]
 | (xhtml:div | div)[@class='title-block_keywords']
 | (xhtml:div | div)[@class='title-block_concepts'][(xhtml:h2 | h2)[@class='keywords_CPT' or @class='keywords_ICD9']]
 | (xhtml:div | div)[@class='figure'][(xhtml:div | div)[@class='swf_locator']]
 | (xhtml:div | div)[@class='section_quickreview'] 
 | (xhtml:div | div)[@class='section_testquestions']
 " />



<!-- these were missing in two procedures and only Described Multimedia Resource was present -->
<xsl:template match="ecm:hasRelationship[not(ecm:Relationship/dct:type/@rdf:resource='ecroles:relatedVideo')][descendant::ecm:targetType]">
	<xsl:variable name="ckid" select="substring-after((ancestor::xhtml:head/xhtml:base | head/base)/@href , ':')"/>
	
	<ecm:hasRelationship>
		<!-- add child -->
		<ecm:Relationship>
			<dct:type rdf:resource="ecroles:relatedVideo"/>
			<ecm:targetResource>
				<xsl:attribute name="rdf:resource" select="concat('mm:', $ckid, '-V')"/>
			</ecm:targetResource>
		</ecm:Relationship>

		<xsl:apply-templates/>
	</ecm:hasRelationship>
</xsl:template>


<!-- expand ecm:hasRelationship for videos -->
<xsl:template match="ecm:hasRelationship[ecm:Relationship/dct:type/@rdf:resource='ecroles:relatedVideo'][not(descendant::ecm:targetType)]">
	<xsl:variable name="ckid" select="substring-after((ancestor::xhtml:head/xhtml:base | head/base)/@href , ':')"/>

	<ecm:hasRelationship>
		<xsl:apply-templates/>
		
		<!-- add child -->
		<ecm:Relationship>
			<dct:title>Described Multimedia Resource</dct:title>
			<ecm:targetResource>
				<xsl:attribute name="rdf:resource" select="concat('mma:procon_', substring-before($ckid, '-'), '/', $ckid, '-HS7210-V-640-0101')"/>
			</ecm:targetResource>
			<ecm:targetType rdf:resource="http://www.elsevier.com/xhtml/SemTypes-1/MultimediaAsset"/>
		</ecm:Relationship>
	</ecm:hasRelationship>
</xsl:template>



<!-- enforce correct structure of document -->
<xsl:template match="(xhtml:div | div)[@class='body']">
	<div>
		<xsl:apply-templates select="@*|comment()"/>
		
		<xsl:apply-templates select=".//(xhtml:div | div)[@class='section_fulldetails_introduction']" mode="restructure"/>
		<xsl:apply-templates select=".//(xhtml:div | div)[@class='section_fulldetails_preprocedure']" mode="restructure"/>
		<xsl:apply-templates select=".//(xhtml:div | div)[@class='section_fulldetails_procedure']" mode="restructure"/>
		<xsl:apply-templates select=".//(xhtml:div | div)[@class='section_fulldetails_postprocedure'] | .//(xhtml:div | div)[@class='section'][(xhtml:h2 | h2)[upper-case(.) = 'POST-PROCEDURE']]" mode="restructure"/>
		<xsl:apply-templates select=".//(xhtml:div | div)[@class='bibliography']" mode="restructure"/>
		<!--REMOVED: <xsl:apply-templates select=".//(xhtml:div | div)[@class='mp_checklist' or @class='section_checklist']" mode="restructure"/>-->
	</div>
</xsl:template>


<!-- eliminate the fulldetails level of nesting (rule probably not needed since it is not explicitly selected when correcting document structure) -->
<xsl:template match="(xhtml:div | div)[@class='section_fulldetails']">
	<xsl:apply-templates/>
</xsl:template>

<!-- exclude these unless explicitly selected when restructuring document (mode=restructure) -->
<xsl:template match="(xhtml:div | div)[@class='section_fulldetails_introduction' or @class='section_fulldetails_preprocedure' or @class='section_fulldetails_procedure' or @class='section_fulldetails_postprocedure' or @class='bibliography' or @class='mp_checklist' or @class='section_checklist' or @class='section'[(xhtml:h2 | h2)[upper-case(.) = 'POST-PROCEDURE']]]"/>

<!-- restructuring document -->
<xsl:template match="xhtml:div | div" mode="restructure">
	<div>
		<xsl:apply-templates select="@*|node()|comment()"/>
	</div>
</xsl:template>

<!-- corrects bad @class when restructuring -->
<xsl:template match="@class[(parent::xhtml:div | parent::div)[@class='section'][(xhtml:h2 | h2)[upper-case(.) = 'POST-PROCEDURE']]]" mode="#unnamed">
	<xsl:attribute name="class">
		<xsl:text>section_fulldetails_postprocedure</xsl:text>
	</xsl:attribute>
</xsl:template>

<!-- these all appear to be in the correct order; normalize @class (AN uses mp_checklist) -->
<!-- REMOVED: 2019-08-19 -->
<xsl:template match="(xhtml:div | div)[@class='mp_checklist' or @class='section_checklist']" mode="restructure">
	<div>
		<xsl:apply-templates select="@*"/>
		<xsl:attribute name="class" select="'section_checklist'"/>
		
		<xsl:apply-templates select="node()|comment()"/>
	</div>
</xsl:template>


<!-- count levels of depth to div.title_block to determine heading level -->
<xsl:template match="(xhtml:h1 | h1 | xhtml:h2 | h2 | xhtml:h3 | h3 | xhtml:h4 | h4 | xhtml:h5 | h5)[@class!='title'][ancestor::*[@class='title-block']]">
	<xsl:variable name="depth_count" select="count(ancestor-or-self::xhtml:div[ancestor-or-self::xhtml:div[@class='title-block']] | ancestor-or-self::div[ancestor-or-self::div[@class='title-block']])"/>
	
	<xsl:element name="h{$depth_count}">
		<xsl:apply-templates select="@*|node()|comment()"/>
	</xsl:element>
</xsl:template>

<!-- same for div.body: with full_details gone, start at h2 -->
<xsl:template match="(xhtml:h1 | h1 | xhtml:h2 | h2 | xhtml:h3 | h3 | xhtml:h4 | h4 | xhtml:h5 | h5)[ancestor::*[@class='body']]">
	<xsl:variable name="depth_count" select="count(ancestor-or-self::xhtml:div[ancestor-or-self::xhtml:div[@class='body']] | ancestor-or-self::div[ancestor-or-self::div[@class='body']]) - 1"/>
	
	<xsl:element name="h{$depth_count}">
		<xsl:attribute name="class" select="'section-title'"/>
		<xsl:apply-templates select="@*|node()|comment()"/>
	</xsl:element>
</xsl:template>

<!-- improper link in source -->
<xsl:template match="(xhtml:a | a)[starts-with(@href, 'javascript')]">
	<xsl:apply-templates select="node()|comment()"/>
</xsl:template>

<!-- fix URLs generally and update pubmed links to current format -->
	<!-- OK: https://www.ncbi.nlm.nih.gov/pubmed/12678357 -->
	<!-- OLD: http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?db=pubmed&amp;&amp;cmd=Retrieve&amp;&amp;dopt=AbstractPlus&amp;&amp;list_uids=%208602680 -->
	<!-- OLD: http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?db=pubmed&amp;cmd=Retrieve&amp;dopt=AbstractPlus&amp;list_uids=%2212356646%22 -->
	<!-- VPN: http://www.ncbi.nlm.nih.gov.offcampus.lib.washington.edu/pubmed/21338862 -->
<!--//a[ancestor::div[@class='bibliography']]/@href[(contains(., '/pubmed/') or contains(., 'query.fcgiZ'))][not(matches(., '^https://www.ncbi.nlm.nih.gov/pubmed/[0-9]+$'))]-->
	<!-- TYPO: https://www.ncbi.nlm.nih.gov/pubmed/15118065https://www.ncbi.nlm.nih.gov/pubmed/15118065 -->
	<!-- TYPO: https://www.ncbi.nlm.nih.gov/pubmed/7722254&#x0026;query_hl=44&#x0026;itool=pubmed_DocSum -->
	<!-- TYPO: https://www.ncbi.nlm.nih.gov/pubmed/11219617&#x0026;itool=iconabstr&#x0026;query_hl=11&#x0026;itool=pubmed_docsum -->
	<!-- TYPO: https://www.ncbi.nlm.nih.gov/pubmed/26683499 (extra space) -->
	<!-- TYPO: https://www.ncbi.nlm.nih.gov/pubmed/12393114\ -->
	<!-- TYPO: https://www.ncbi.nlm.nih.gov/pubmed/12101150. -->
	<!-- TYPO: https://www.ncbi.nlm.nih.gov/pubmed/17612430%20 -->
	<!-- ILLEGAL: javascript:AL_get(this,%20'jour',%20'N%20Engl%20J%20Med.'); -->
	<!-- TYPO: 1736514 -->
	<!-- TYPO: QuickReview.aspx?RefNum=AN-021 (s/b mp:AN-021) -->
	<!-- TYPO: not(^http|https|#), but span -->
<xsl:template match="@href[parent::xhtml:a or parent::xhtml:a][not(matches(., '^https://www.ncbi.nlm.nih.gov/pubmed/[0-9]+$') or starts-with(., '#') or starts-with(., 'mp:'))]">
	<xsl:attribute name="href">
		<xsl:choose>
			<!-- old pubmed format -->
			<xsl:when test="contains(., '/entrez/query.fcgi')">
				<xsl:text>https://www.ncbi.nlm.nih.gov/pubmed/</xsl:text>
				<xsl:value-of select="replace(., '^.+%2[02]([0-9]+)([^0-9]+|[^0-9]+[0-9]+)?$', '$1')"/>
			</xsl:when>
			<!-- VPN removal and correction of protocol -->
			<xsl:when test="contains(., '.offcampus.lib.washington.edu/pubmed/')">
				<xsl:value-of select="replace(replace(., '.offcampus.lib.washington.edu', ''), 'http:', 'https:')"/>
			</xsl:when>
			<!-- remove trailing characters from otherwise standard links -->
			<xsl:when test="matches(., '^https://www.ncbi.nlm.nih.gov/pubmed/[0-9]+')">
				<xsl:value-of select="replace(., '^(https://www.ncbi.nlm.nih.gov/pubmed/[0-9]+).+$', '$1')"/>
			</xsl:when>
			<!-- orphaned pubmed ID -->
			<xsl:when test="matches(., '^[0-9]+$')">
				<xsl:text>https://www.ncbi.nlm.nih.gov/pubmed/</xsl:text><xsl:value-of select="."/>
			</xsl:when>
			<!-- corrects error in source -->
			<xsl:when test="contains(., 'QuickReview.aspx')">
				<xsl:text>mp:</xsl:text>
				<xsl:value-of select="substring-after(., '=')"/>
			</xsl:when>
			<!-- adds missing # to ID reference -->
			<xsl:when test="starts-with(., 'span')">
				<xsl:text>#</xsl:text>
				<xsl:value-of select="."/>
			</xsl:when>
			<!-- adds missing http where needed (# and mp: are pedantically excluded) -->
			<xsl:when test="not(starts-with(., 'http') or starts-with(., '#') or starts-with(., 'mp:'))">
				<xsl:text>http://</xsl:text>
				<xsl:value-of select="."/>
			</xsl:when>
			
			<!-- don't fix these; they should be OK; useful for catching errors -->
			<xsl:otherwise>
				<xsl:value-of select="."/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:attribute>
</xsl:template>

<!-- corrects error in source -->
<xsl:template match="@class[.='IntraProcedureJump']">
	<xsl:attribute name="rel" select="'ecroles:intra-ref'"/>
</xsl:template>


<xsl:template match="xhtml:b | b">
	<strong>
		<xsl:apply-templates select="@*|node()|comment()"/>
	</strong>
</xsl:template>

<xsl:template match="xhtml:i | i">
	<em>
		<xsl:apply-templates select="@*|node()|comment()"/>
	</em>
</xsl:template>



</xsl:stylesheet>