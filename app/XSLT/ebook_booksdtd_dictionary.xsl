<?xml version="1.0"?>
<xsl:stylesheet version="3.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:bk="http://www.elsevier.com/xml/bk/dtd"
				xmlns:html="http://www.w3.org/1999/xhtml"
				xmlns:aid="http://ns.adobe.com/AdobeInDesign/4.0/"
				xmlns:sb="http://www.elsevier.com/xml/common/struct-bib/dtd"
				xmlns:ce="http://www.elsevier.com/xml/common/dtd"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				xmlns:mml="http://www.w3.org/Math/DTD/mathml2/mathml2.dtd"
				exclude-result-prefixes="bk html aid sb ce xlink mml">
	<!-- Book.dtd Dictionary content to Dictionary DTD; derived from ebook_booksdtd.xsl; bk namespace is default, sb is not defined in XML and therefore in default namespace, aid is only used in proofs
	2017-06-28 JWS	original
	2017-07-27 JWS	removed normalize-space from text() rule eliminating &#x2002; because it deleted spaces around <xref> in particular; updated xref/@refid (intra-ref/cross-ref are combined, which is slow)
	2017-08-17 JWS	component/@availability=electronic fixed; removed _REPLACE_LOCATION__ from file
	2019-10-10 JWS	updates when processing DMAA; generates entity_ids; honors $category='NONE' param
	2020-04-30 JWS	DMAA image characters; xrefs and entity_id for non-current nodes; hairspaces and other spaces; definition-contained etymologies/etymons; defgroup_type for DMAA



	0. be sure your XSL transformation tool supports XSLT 3
	1. copy XSLT's DOCTYPE over the source XML's DOCTYPE. This removes the unfollowable reference to the old DTD and adds support for known entities
	2. transform the source XML against this XSLT
		a. add any missing entities to both the source XML and this XSLT
		b. use $devmode='missed' or 'Y' to check for missed elements, retain original IDs or other things you'd want to do during testing
		c. verify that other params are set as you'd like them, eg, $category, $defmarker, $imagenaming, $syl
		d. check partofspeech text patterns
	3. save transformed XML in a batch directory following this naming convention letter_a_4e.xml (lowercase and edition included)
		a. the main Dictionary DTD needs to be followable to ensure the output is valid. Default location is Y:\WWW1\METIS\Dictionary_4_3.dtd and can be changed in this XSLT
	4. ZIP batch directory and upload to converted_XML bucket in project's bucket on S3
	 -->
	 
<!--
**************************************************
**************************************************
*	TODO:
**************************************************
**************************************************

		1. assign entity_id - done
		1a. xref: cross-ref, intra-ref - done
		2. ce:monospace - de-italicized source XML and added comment
		3. ce:sans-serif - updated rule to catch this input
		4. etymology - [L] - ignored since many atoms have one for each of multiple defs and DTD does not support this
		5. make category optional by honoring $category - set param before processing
		6. set element ids - done
		7. <file> letters - done
		8. remove hairspaces - done
	9. multiple definitions split on semicolon - will not do

-->


<xsl:output method="xml" encoding="utf-8" indent="yes"
 omit-xml-declaration="yes"
 doctype-public="-//ES//DTD dictionary DTD version 1.0//EN//XML" doctype-system="https://major-tool-development.s3.amazonaws.com/DTDs/Dictionary_4_8.dtd"
 media-type="text/html"
 />

<xsl:preserve-space elements="br"/>

  	<!-- here just in case they need to be; delete if not used -->
  <xsl:param name="thispage" select="'NONE'"/>	<!-- optional; indicates the requesting page path; requires usage_type to be able to look for a specific item -->
  <xsl:param name="asset_location" select="'book/'"/>
  <xsl:param name="searchedpage" select="'NONE'"/>	<!-- optional; path of page being searched -->
  <xsl:param name="searchedpage_type" select="'searchedpage'"/>
  <xsl:param name="browser"/>
  <xsl:param name="sectionID" select="'NONE'"/>	<!-- optional; requests a specific ID on the XML document to look for -->
  <xsl:param name="devmode" select="'N'"/>	<!-- "missed" will copy nodes without rules; "brokenimage" will hide FigureCaption in production when images are missing; "refid" will output additional data for testing cross/intra-ref; "Y" replaces broken images with error message -->
  <xsl:param name="refidcalc" select="true()"/>	<!-- true will populate refid in cross/intra-ref with calculated entity_ids; false will use tra_alphatitle instead and require a quickfix in METIS later -->
  <xsl:param name="category" select="'main'"/>	<!-- default, main, ensures even empty category will be output on main entries only, sub will include in both main and subentries, N prevents empty category -->
  <xsl:param name="defgroup_type">	<!-- default, shared, sets the defgroup to be available for all products; pass a value or trust the ISBN match -->
	<xsl:choose>
		<xsl:when test="//bk:info[1]/ce:isbn='978-0-323-34020-5'">abbreviations</xsl:when>	<!-- DMAA -->
		<xsl:otherwise>
			<xsl:text>shared</xsl:text>
		</xsl:otherwise>
	</xsl:choose>
  </xsl:param>  
   <xsl:param name="form_start" select="'('"/>	<!-- string marker to determine whether the form element should be output for a definition -->
	  <xsl:param name="form_end" select="')'"/>	<!-- end -->
  <xsl:param name="def_splitter" select="false()"/>	<!-- normally false; character on which to split definitions combined as in DMAA -->
  <xsl:param name="imagenaming" select="'simple'"/>	<!-- "simple" will transform images from on133-040-9780323172929.jpg to 133040.jpg; convention-XX or convention-XX.thumb_.fullsize -->
  <xsl:param name="inlineasthumb" select="'NONE'"/>	<!-- IDs of inline-figures that will be displayed as thumbnails surrounded and separated with |: |fx1|fx2| -->
  <xsl:param name="search" select="'NOSEARCH'"/>
  <xsl:param name="search_term" select="'NOSEARCH'"/>
  <xsl:variable name="search_term_UPPER" select="translate($search_term, 'abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ')"/>
	<xsl:variable name="newline">
		<xsl:text>
</xsl:text>
	</xsl:variable>

  <xsl:variable name="document_root">
    <xsl:choose>
      <xsl:when test="/bk:chapter">
        <xsl:text>chapter</xsl:text>
      </xsl:when>
      <xsl:when test="/bk:fb-non-chapter">
        <xsl:text>fb-non-chapter</xsl:text>
      </xsl:when>
      <!-- TODO: not in use in any of the chapters put into production yet, but present in other files; this has not been applied to the rest of this XSL yet -->
      <xsl:when test="/bk:introduction">
        <xsl:text>introduction</xsl:text>
      </xsl:when>
      <xsl:when test="/bk:index">
        <xsl:text>index</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:text>/</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:variable>
  <xsl:variable name="document_location">
    <xsl:choose>
      <xsl:when test="/bk:fb-non-chapter and /bk:fb-non-chapter/@docsubtype='htu'">
        <xsl:text>front</xsl:text>
      </xsl:when>
      <xsl:when test="/bk:introduction or /bk:chapter"><!--@docsubtype="chp|itr"-->
        <xsl:text>body</xsl:text>
      </xsl:when>
      <xsl:when test="(/bk:fb-non-chapter and /bk:fb-non-chapter/@docsubtype='app') or /bk:index">
        <xsl:text>rear</xsl:text>
      </xsl:when>
      <xsl:otherwise></xsl:otherwise>
    </xsl:choose>
  </xsl:variable>
  <xsl:variable name="document_id" select="/bk:chapter/@id|/bk:fb-non-chapter/@id|/bk:index/@id"/> <!-- c00001 -->
  	<xsl:variable name="document_type" select="substring($document_id,1,1)"/>
  	<xsl:variable name="document_subtype" select="/bk:chapter/@docsubtype|/bk:fb-non-chapter/@docsubtype|/bk:index/@docsubtype"/> <!-- chp -->
  	<xsl:variable name="document_isbn" select="translate(//bk:chapter[1]/bk:info/ce:isbn|//bk:fb-non-chapter[1]/bk:info/ce:isbn|//bk:index[1]/bk:info/ce:isbn, '-', '')"/>
  	<xsl:variable name="document_pii" select="translate(/bk:chapter/bk:info/ce:pii|/bk:fb-non-chapter/bk:info/ce:pii|/bk:index/bk:info/ce:pii, '-.', '')"/>
  	<xsl:variable name="document_copyright" select="/bk:chapter/bk:info/ce:copyright|/bk:fb-non-chapter/bk:info/ce:copyright|/bk:index/bk:info/ce:copyright"/>
  	<xsl:variable name="document_copyright_year" select="/bk:chapter/bk:info/ce:copyright/@year|/bk:fb-non-chapter/bk:info/ce:copyright/@year|/bk:index/bk:info/ce:copyright/@year"/>
  	<!--<xsl:variable name="document_section" select="substring($document_id,15,1)"/>-->
  	<xsl:variable name="document_chapter" select="substring($document_id,2)"/>
  		<xsl:variable name="document_chapter_zeroed"><xsl:number value="$document_chapter" format="001" /></xsl:variable>
  	<!--<xsl:variable name="document_checkdigit" select="substring($document_id,20,1)"/>-->


<xsl:key 
  name="definitions" 
  match="//ce:def-description"
  use="concat(ancestor::bk:chapter/@id, '_', preceding-sibling::ce:def-term[1]/@id)"/>


<xsl:param name="output_tree" select="'false'"/> <!-- set to true to output the $uuid_tree fragment used for key generation when ALSO in devmode=Y -->
<!-- used to calculate the UUIDs, use the same values for each transformation of a single title to ensure the same values are calculated each time; recommend changing this for new titles -->
	<xsl:param name="uuid_multiplier" select="28846"/>
	<xsl:param name="uuid_increment" select="49445"/>
	<xsl:param name="uuid_divisor" select="15356"/>

<xsl:param name="existing_entityid_file" select="'NONE'"/> <!-- the location of the file containing the entity_ids already assigned in METIS; set to NONE if they have not been assigned -->

<xsl:variable name="entityids" select="document($existing_entityid_file)" as="document-node()"/>

<!-- unique identifier for previously assigned entity_ids -->
<xsl:key name="metisID" match="//atom" use="@title"/><!-- for a dictionary, concat(chapter letter, '::', headw/@id) -->

<!-- find assigned entity_id in $entityids -->
<xsl:template name="get_entity_id">
	<!-- node to find an entity_id for if not the current one -->
	<xsl:param name="find_node" select="current()"/>
	
	<xsl:if test="$existing_entityid_file != 'NONE'">
		<xsl:variable name="temp" select="concat($find_node/ancestor::chapter/title, $find_node/ancestor::bk:chapter/ce:title, '::', $find_node/@id)"/>
		<xsl:value-of select="$entityids/key('metisID',$temp)/@entity_id"/>
	</xsl:if>
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
            <xsl:with-param name="atoms" select="//bk:chapter/ce:sections/ce:para/ce:def-list/ce:def-term"/>
            <xsl:with-param name="counter" select="1"/>
        </xsl:call-template>
    </uuids>
</xsl:variable>

<!-- keys used to improve the efficiency of applying UUIDs to atoms -->
<!-- NOTE: if document has a default namespace, this will have to use it -->
<xsl:key name="uuid_key" match="$uuid_tree//atom" use="@id"/>
<xsl:key name="uuid_uniquenode" match="$uuid_tree//atom" use="@uniquenode"/>


<!-- populates $uuid_tree with a pseudo-UUID from a seed, based on the Linear Congruential Generator: Xn+1 = (aXn + c) mod c -->
<!-- $atoms are the set of elements in source needing UUIDs once transformed into atoms -->
<xsl:template name="generateUUID">
    <!-- This seed begins as $entity_id_base, but once generateUUID is recursing it becomes the previous atom's UUID -->
    <xsl:param name="seed"/>
    <!-- Select the unique nodes in source that will require a UUID upon transformation -->
    <xsl:param name="atoms"/>
    <xsl:param name="counter"/><!-- USED TO LIMIT EXPENSE OF KEY CALCULATION when $devmode=Y -->
   
    <!--($a * $seed + $c) mod $m-->
    <xsl:variable name="uuid" select="($uuid_multiplier * $seed + $uuid_increment) mod $uuid_divisor"/>
   
    <!-- first atom in $atoms -->
    <xsl:element name="atom">
        <xsl:attribute name="uuid" select="format-number($uuid, '999999')"/>
        <xsl:attribute name="genid" select="generate-id($atoms[position()=1])"/>
        <xsl:attribute name="uniquenode" select="$atoms[position()=1]/concat(ancestor::bk:chapter/ce:title, '::', @id)"/>    <!-- a distinct per atom, omnipresent node(s) to test pairing source atoms with generated IDs -->
        <xsl:attribute name="count" select="count($atoms)"/>
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



<!-- root is manually added when having concatenated multiple chapters into a single document -->
<xsl:template match="/bk:root|/root|/chapter|/bk:chapter|/fb-non-chapter|/bk:fb-non-chapter|/bk:index">
	<dictionary isbn="{$document_isbn}">
		<!-- outputs contents of tree fragment used for key generation when $devmode=Y and $output_tree=true-->
		<xsl:if test="$devmode='Y' and $output_tree='true'">
			<xsl:copy-of select="$uuid_tree"/>
		</xsl:if>
		<body>
			<xsl:apply-templates/>
		</body>
	</dictionary>
</xsl:template>

<!-- alternate for concatenated chapters so body isn't repeated -->
<xsl:template match="chapter[parent::root]|bk:chapter[parent::bk:root]">
	<xsl:apply-templates/>
</xsl:template>

<xsl:template match="ce:sections">
	<alpha>
		<!-- normalize initial letter -->
		<xsl:attribute name="letter"><xsl:value-of select="translate(upper-case(preceding-sibling::ce:title), 'αβγδζΑΒΓΔΖ', 'abgdzABGDZ')"/></xsl:attribute>

		<xsl:apply-templates/>
	</alpha>

</xsl:template>

<!-- hides chapter information at beginning -->
<xsl:template match="bk:info"/>

<!-- hides chapter title at beginning -->
<xsl:template match="ce:title[parent::bk:chapter]"/>

<!-- mostly these are containers without a need for a corresponding element in the output; def-list here is main entry level -->
<xsl:template match="ce:para|ce:def-list[parent::ce:para[parent::ce:sections]]">
	<xsl:apply-templates/>
</xsl:template>

<!-- de-nests subentries from definitions -->
<xsl:template match="ce:def-list" mode="subentry">
	<xsl:apply-templates/>
</xsl:template>

<xsl:template match="ce:def-term">
	<!-- when called by name the context will be the html node rather than @id; this adapts for both situations -->
	<xsl:variable name="find_id" select="current()/@id"/>

	<xsl:variable name="subentrylevel" select="count(ancestor::ce:def-list)"/>

	<entry sortorder="{count(preceding::ce:def-term) + 1}" type="shared">
		<xsl:attribute name="id">
			<xsl:call-template name="set_atom_id"/>
		</xsl:attribute>
		<headw>
			<xsl:call-template name="id_generator">
				<xsl:with-param name="nodename" select="'headw'"/>
			</xsl:call-template>

			<xsl:apply-templates/>
		</headw>
		<xsl:if test="not(bk:future_classification_element)">
			<xsl:call-template name="category"/>
		</xsl:if>
		<xsl:apply-templates select="bk:classification_element_of_some_kind"/>
		<xsl:apply-templates select="following-sibling::ce:def-description[1]" mode="form"/>
		<xsl:apply-templates select="key('definitions', concat(ancestor::bk:chapter/@id, '_', @id))" mode="entry"/>
		<!-- Cautiously select only the next generation of subentries -->
		<xsl:apply-templates select="following-sibling::ce:def-description[1]/descendant::ce:para/ce:def-list[count(ancestor::ce:def-list) = $subentrylevel]" mode="subentry"/>
	</entry>
</xsl:template>


<!-- fixes text -->
<xsl:template match="text()" mode="#all">
	<xsl:value-of select="replace(translate(., ' ', ''), '[\t\p{Zs}]+', ' ')"/><!-- delete hairspace (U+200A); then normalize other spaces (eg, &#x2002; ce:def-description contained N-space and space and N-space is not treated as whitespace so defgroup was not valid) -->
</xsl:template>

<!-- BEGIN visual style templates -->
<!-- TODO: add mode="#current" to more than just it (used by plural) -->
<!-- regular set -->
<xsl:template match="b|ce:bold" mode="#all">
  <emphasis style="bold"><xsl:apply-templates mode="#current"/></emphasis>
</xsl:template>
<xsl:template match="bolditalic" mode="#all">
  <emphasis style="bold"><emphasis style="italic"><xsl:apply-templates mode="#current"/></emphasis></emphasis>
</xsl:template>
<xsl:template match="inf|ce:inf" mode="#all">
  <emphasis style="inf"><xsl:apply-templates mode="#current"/></emphasis>
</xsl:template>
<xsl:template match="it|ce:italic" mode="#all">
  <emphasis style="italic"><xsl:apply-templates mode="#current"/></emphasis>
</xsl:template>
<!-- this probably is an indication the source XML needs to be cleaned up: monospace has been used to de-italicize text within italicized text -->
<xsl:template match="monospace|ce:monospace" mode="#all">
	<missed class="devmode">
		<xsl:comment>monospace is an uncommon style, it may be necessary to clean up source XML</xsl:comment>
		<emphasis style="monospace"><xsl:apply-templates mode="#current"/></emphasis>
	</missed>
</xsl:template>
<xsl:template match="sans-serif|ce:sans-serif" mode="#all">
  <emphasis style="sans-serif"><xsl:apply-templates mode="#current"/></emphasis>
</xsl:template>
<xsl:template match="sc|ce:small-caps" mode="#all">
  <emphasis style="smallcaps"><xsl:apply-templates mode="#current"/></emphasis>
</xsl:template>
<xsl:template match="sup|ce:sup" mode="#all">
  <emphasis style="sup"><xsl:apply-templates mode="#current"/></emphasis>
</xsl:template>
<xsl:template match="underline" mode="#all">
  <emphasis style="underline"><xsl:apply-templates mode="#current"/></emphasis>
</xsl:template>
<!-- END visual style templates -->



<xsl:template name="category">
	<!-- METIS always wants these present if it supports setting them from outside the atom editing screen; it also cannot handle self-closed elements, hence this ugly mess -->
	<!--<xsl:if test="($category='main' and count(ancestor::ce:def-list) = 1) or $category='sub'">
		<xsl:value-of select="$newline"/>
		<xsl:text disable-output-escaping="yes">				&lt;category cat_id="catNONE"&gt;&lt;/category&gt;</xsl:text>
		<xsl:value-of select="$newline"/>
		<xsl:text disable-output-escaping="yes">				</xsl:text>
	</xsl:if>-->
	<xsl:if test="$category != 'NONE'">
		<category cat_id="{concat('cat', 'NONE')}"><xsl:text> </xsl:text></category>
	</xsl:if>
</xsl:template>

<!-- as-yet undefined classification element in source XML should always be included in entry -->
<xsl:template match="bk:future_classification_element">
	<category cat_id="{concat('cat', '_REPLACE_ME__')}">
		<xsl:apply-templates/>
	</category>
</xsl:template>

<!-- subentries, but must be selected outside def-description to create correct ancestral tree -->
<xsl:template match="ce:def-list[ancestor::ce:def-description]" mode="entry"/>

<xsl:template match="ce:def-description" mode="form"/>

<!-- form includes etymology, plural and pronunciation -->
<xsl:template match="ce:def-description[descendant::ce:italic/ce:bold[contains(., $form_start) or contains(., $form_end)]]" mode="form">
	<form>
		<!-- usually @id is copied, this enforces required presence and uniqueness -->
		<xsl:call-template name="id_generator">
			<xsl:with-param name="nodename" select="'form'"/>
		</xsl:call-template>	

		<!-- careful to only select pronunciations for the current entry -->
		<xsl:apply-templates select="ce:para/ce:italic/ce:bold[contains(., $form_start) or contains(., $form_end)]|ce:para/ce:inter-ref/ce:italic/ce:bold[contains(., $form_start) or contains(., $form_end)]" mode="pronunciation"/>
		<!--TODO: <xsl:apply-templates select="ce:inter-ref" mode="plural"/>-->
		<!--<xsl:apply-templates select="def[1]" mode="etymology"/>-->
	</form>
</xsl:template>


<!-- pronunciation with audio component (only place MP3s are found) -->
<xsl:template match="ce:inter-ref" mode="pronunciation">
	<component type="audio">
		<!-- usually @id is copied, this enforces required presence and uniqueness -->
		<xsl:call-template name="id_generator">
			<xsl:with-param name="nodename" select="'audio'"/>
		</xsl:call-template>	
	
		<file src="{@xlink:href}">
			<xsl:if test="$devmode = 'Y'"><xsl:attribute name="id_legacy" select="@id"/></xsl:if>
		</file>
	</component>
</xsl:template>

<xsl:template match="ce:italic" mode="pronunciation">
	<xsl:apply-templates mode="#current"/>
</xsl:template>

<!-- pronunciation text should have no further markup -->
<xsl:template match="ce:bold" mode="pronunciation">
	<pronun>
		<xsl:apply-templates mode="#current"/>
		<xsl:apply-templates select="ancestor::ce:inter-ref" mode="#current"/>
	</pronun>
</xsl:template>

<!-- cleans up pronunciation text: (foo), transformed to foo; pronunciations are generally trailed by en space -->
<xsl:template match="text()" mode="pronunciation">
	<xsl:value-of select="replace(normalize-space(translate(., ' ', ' ')), '^\(?([^)]+)\)?,?$*', '$1')"/>
</xsl:template>

<!-- TODO: check this list /root/chapter/ce:sections/ce:para/ce:def-list/ce:def-description/ce:para//ce:italic[not(ancestor::ce:inter-ref) and not(text()='n') and not(text()='n.') and not(text()='n.pl') and not(text()='n. pl') and not(text()='n/n.pl')  and not(text()='n.pr') and not(text()='n.pr.') and not(text()='n.pr.pl') and not(text()='n.pr/pl') and not(text()='n.pr/n.pl') and not(text()='pr.n') and not(text()='n/adj') and not(text()='n/v') and not(text()='v/n') and not(text()='adj') and not(text()='adj/n') and not(text()='adv') and not(text()='adj/adv') and not(text()='v') and not(text()='pre') and not(text()='pref') and not(text()='n brand name:') and not(text()='n brand names:') and not(text()='n brand name:') and not(text()='use:')  and not(text()='uses:') and not(text()='action:') and not(text()='actions:') and not(text()='drug class:')] -->

<!-- ignore -->
<xsl:template match="ce:def-description"/>

<xsl:template match="ce:def-description" mode="entry">
	<xsl:variable name="subentrylevel" select="count(ancestor::ce:def-list)"/>
	<defgroup>
		<xsl:attribute name="type" select="$defgroup_type"/>
		<xsl:apply-templates mode="#current"/>
	</defgroup>
	<!-- these figures need to be placed after and outside defgroup, but inside entry and before subentries -->
	<xsl:apply-templates select="ce:para/ce:display[preceding-sibling::ce:list]" mode="entry_figure"/>
	<!-- Cautiously select only the next generation of subentries -->
	<xsl:if test="parent::ce:DISABLEDdef-list[parent::ce:para[parent::ce:sections]]">
		<b><xsl:apply-templates select="descendant::ce:para/ce:def-list[count(ancestor::ce:def-list) = $subentrylevel]" mode="subentry"/></b>
	</xsl:if>

</xsl:template>
	
<xsl:template match="ce:list" mode="entry">
	<ol>
		<xsl:apply-templates mode="#current"/>
	</ol>
</xsl:template>

<xsl:template match="ce:list-item" mode="entry">
	<li>
		<xsl:apply-templates mode="#current"/>
	</li>
</xsl:template>

<!-- drop all of these -->
<xsl:template match="ce:label" mode="entry"/>

<xsl:template match="ce:para|ce:list-item[matches(ce:label, '[0-9].')]" mode="entry">
	<def>
		<!-- usually @id is copied, this enforces required presence and uniqueness -->
		<xsl:call-template name="id_generator">
			<xsl:with-param name="nodename" select="'def'"/>
		</xsl:call-template>
		<xsl:attribute name="n">
			<xsl:choose>
				<xsl:when test="ce:label"><xsl:value-of select="translate(ce:label, '.', '')"/></xsl:when>
				<xsl:otherwise><xsl:value-of select="count(preceding-sibling::ce:para) + 1"/></xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
		<xsl:apply-templates select="parent::ce:list/preceding-sibling::ce:italic[1]" mode="partofspeech"/>
		<xsl:apply-templates select="parent::ce:list/preceding-sibling::ce:italic[1]/following-sibling::text()" mode="thesaurus"/>
		<xsl:apply-templates mode="#current"/>
	</def>
</xsl:template>

<!-- inline etymologies from DMAA -->
<xsl:template match="text()[
	contains(., '[Fr. ') or
	contains(., '[Ger. ') or
	contains(., '[L. ') or
	contains(., '[Lat. ')][following-sibling::ce:italic[following-sibling::text()[contains(., ']')]]][ancestor::ce:def-description]" mode="entry">
	
	<xsl:value-of select="substring-before(., '[')"/>
	<xsl:text>[</xsl:text>
	<xsl:call-template name="etymology"/>
</xsl:template>

<!-- creates linline etymologies for DMAA -->
<xsl:template name="etymology">
	<xsl:variable name="lang" select="substring-before(substring-after(., '['), '. ')"/>
	<etymology>
		<lang>
			<xsl:attribute name="group">
				<xsl:choose>
					<xsl:when test="$lang = 'Fr'">french</xsl:when>
					<xsl:when test="$lang = 'Ger'">german</xsl:when>
					<xsl:when test="$lang = 'L' or $lang = 'Lat'">latin</xsl:when>
					<xsl:otherwise>ERROR: <xsl:value-of select="$lang"/></xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
			<xsl:value-of select="$lang"/><xsl:text>.</xsl:text>
		</lang>
		<xsl:text> </xsl:text>
		<xsl:apply-templates select="following-sibling::*[1][local-name()='italic']" mode="etymon"/>
	</etymology>

</xsl:template>

<!-- part of inline etymology for DMAA -->
<xsl:template match="ce:italic[preceding-sibling::text()[1][contains(., '[')]]" mode="#all" priority="0"/>

<!-- the source word for DMAA's inline etymologies -->
<xsl:template match="ce:italic" mode="etymon">
	<etymon>
		<xsl:apply-templates/>
	</etymon>
</xsl:template>

<!-- TODO: unnumbered definitions are separated by semicolons in DMAA; requires setting $def_splitter -->
<!--<xsl:template match="ce:para[parent::ce:def-description][contains(., $def_splitter)]" mode="entry">
	<xsl:for-each-group select="tokenize(., ';')" group-starting-with="';'">
		<def grouper="{$def_splitter}">
			<xsl:value-of select="."/>
			<xsl:apply-templates select="."/>-->
			<!--<xsl:apply-templates select="current-group()" mode="#current"/>-->
<!--		</def><xsl:text>
</xsl:text>
	</xsl:for-each-group>
</xsl:template>-->

<!-- numbered definitions -->
<xsl:template match="ce:para[ce:list/ce:list-item[matches(ce:label, '[0-9].')]]|ce:list[ce:list-item[matches(ce:label, '[0-9].')]]" mode="entry">
	<xsl:apply-templates mode="#current"/>
</xsl:template>

<xsl:template match="ce:para[parent::ce:list-item]" mode="entry">
	<xsl:apply-templates mode="#current"/>
</xsl:template>


<!-- sort of a cross-reference, thesaurus type entry found in Dental Dictionary that should instead be part of following def -->
<xsl:template match="text()[parent::ce:para[ce:list]][matches(., '[\w]+')]" mode="entry"/>

<xsl:template match="text()" mode="thesaurus"/>

<xsl:template match="text()[parent::ce:para[ce:list]][matches(., '[\w]+')]" mode="thesaurus">
	<para>
		<!-- usually @id is copied, this enforces required presence and uniqueness -->
		<xsl:call-template name="id_generator">
			<xsl:with-param name="nodename" select="'para'"/>
		</xsl:call-template>

		<xsl:value-of select="."/>
	</para>
</xsl:template>




<!-- TODO: this will need to be tuned to each title -->
<!-- Dental Dictionary -->
<xsl:template match="ce:italic[text()='n' or text()='n.' or text()='n.pl' or text()='n. pl' or text()='n/n.pl' or text()='n.pr' or text()='n.pr.' or text()='n.pr.pl' or text()='n.pr/pl' or text()='n.pr/n.pl' or text()='pr.n' or text()='n/adj' or text()='n/v' or text()='v/n' or text()='adj' or text()='adj/n' or text()='adv' or text()='adj/adv' or text()='v' or text()='pre' or text()='pref']" name="partofspeech" mode="entry">
	<part-of-speech><xsl:apply-templates mode="#current"/></part-of-speech>
</xsl:template>

<!-- ignores part-of-speech when placed immediately before a ce:list because it needs to be within the def, not outside it -->
<xsl:template match="ce:italic[text()='n' or text()='n.' or text()='n.pl' or text()='n. pl' or text()='n/n.pl' or text()='n.pr' or text()='n.pr.' or text()='n.pr.pl' or text()='n.pr/pl' or text()='n.pr/n.pl' or text()='pr.n' or text()='n/adj' or text()='n/v' or text()='v/n' or text()='adj' or text()='adj/n' or text()='adv' or text()='adj/adv' or text()='v' or text()='pre' or text()='pref'][following-sibling::ce:list]" mode="entry"/>

<!-- calls the earlier ignored part-of-speech rule by name to relocate within ce:list defs -->
<xsl:template match="ce:italic" mode="partofspeech">
		<xsl:call-template name="partofspeech"/>
</xsl:template>


<!-- MP3s captured with pronun -->
<xsl:template match="ce:inter-ref" mode="entry"/>

<!-- video links -->
<xsl:template match="ce:inter-ref[contains(@xlink:href, '.flv')]" mode="entry">
	<component type="video">
		<!-- usually @id is copied, this enforces required presence and uniqueness -->
		<xsl:call-template name="id_generator">
			<xsl:with-param name="nodename" select="'video'"/>
		</xsl:call-template>

		<file src="{@xlink:href}">
			<xsl:if test="$devmode = 'Y'"><xsl:attribute name="id_legacy" select="@id"/></xsl:if>
		</file>
	</component>

</xsl:template>

<xsl:template match="ce:display" mode="#all">
	<xsl:apply-templates/>
</xsl:template>

<!-- removes figures from defgroup so they may be relocated as child of entry -->
<xsl:template match="ce:display[preceding-sibling::ce:list]" mode="entry"/>

<!-- this is the relocator into entry -->
<xsl:template match="ce:display[preceding-sibling::ce:list]" mode="entry_figure">
	<xsl:apply-templates mode="#current"/>
</xsl:template>

<!-- used for figures in entry or inline -->
<xsl:template match="ce:figure" mode="#all">
	<xsl:call-template name="figure"/>

</xsl:template>


<!-- BEGIN/TODO: images unique to each title will need to be replaced for each transformation -->
<!-- these were used to indicate in print that a e-only image was available, ce:para/@extended is more reliable and useful and the icon isn't needed in XML -->
<xsl:template match="ce:inline-figure[ce:link[@locator='icon01-9780323100120']]" mode="#all"/>

<!-- these were used to indicate in print that a video (Archie) available but it is unnecessary -->
<xsl:template match="ce:inline-figure[ce:link[@locator='icon02-9780323100120']]" mode="#all"/>

<!-- BEGIN fractions; ordered in increasing size -->
<xsl:template match="ce:inline-figure[ce:link[@locator='if016-003-9780323100120']]" mode="#all">
	<math><mfrac bevelled="true"><mi>1</mi><mi>128</mi></mfrac></math>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if016-004-9780323100120']]" mode="#all">
	<math><mfrac bevelled="true"><mi>1</mi><mi>96</mi></mfrac></math>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if016-005-9780323100120']]" mode="#all">
	<math><mfrac bevelled="true"><mi>1</mi><mi>64</mi></mfrac></math>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if016-006-9780323100120']]" mode="#all">
	<math><mfrac bevelled="true"><mi>1</mi><mi>48</mi></mfrac></math>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if016-007-9780323100120']]" mode="#all">
	<math><mfrac bevelled="true"><mi>1</mi><mi>32</mi></mfrac></math>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if016-008-9780323100120']]" mode="#all">
	<math><mfrac bevelled="true"><mi>1</mi><mi>16</mi></mfrac></math>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if016-002-9780323100120']]" mode="#all">
	<math><mfrac bevelled="true"><mi>1</mi><mi>8</mi></mfrac></math>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if014-001-9780323100120' or @locator='if020-001-9780323100120']]" mode="#all">
	<math><mfrac bevelled="true"><mi>1</mi><mi>4</mi></mfrac></math>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if016-001-9780323100120']]" mode="#all">
	<math><mfrac bevelled="true"><mi>3</mi><mi>8</mi></mfrac></math>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if019-001-9780323100120']]" mode="#all">
	<math><mfrac bevelled="true"><mi>1</mi><mi>2</mi></mfrac></math>
</xsl:template>
<!-- END fractions -->

<!-- BEGIN special characters (DMAA); ordered by figure number -->
<xsl:template match="ce:inline-figure[ce:link[@locator='if003-001-9780323340205']]" mode="#all">
	<xsl:text>C</xsl:text><emphasis style="obar">1</emphasis>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if003-009-9780323340205']]" mode="#all">
	<emphasis style="obar">9</emphasis>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if003-010-9780323340205']]" mode="#all">
	<emphasis style="obar">c</emphasis>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if003-011-9780323340205']]" mode="#all">
	<xsl:text>C[a &amp; </xsl:text><emphasis style="obar">v</emphasis><xsl:text>]O</xsl:text><emphasis style="inf">2</emphasis>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if004-001-9780323340205']]" mode="#all">
	<emphasis style="obar">D</emphasis>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if004-002-9780323340205']]" mode="#all">
	<emphasis style="obar">d</emphasis>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if016-001-9780323340205']]" mode="#all">
	<emphasis style="obar">P</emphasis>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if017-001-9780323340205']]" mode="#all">
	<xsl:text>Q̇</xsl:text><!-- combining character: dot above (U+0307) -->
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if019-001-9780323340205']]" mode="#all">
	<xsl:text>S</xsl:text><emphasis style="inf"><emphasis style="obar">x</emphasis></emphasis>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if019-002-9780323340205']]" mode="#all">
	<xsl:text>SE(</xsl:text><emphasis style="obar">x</emphasis><xsl:text>)</xsl:text>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if019-003-9780323340205']]" mode="#all">
	<emphasis style="obar">S</emphasis>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if020-001-9780323340205']]" mode="#all">
	<emphasis style="obar">T</emphasis><emphasis style="inf">c</emphasis><xsl:text>NM</xsl:text>
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if022-001-9780323340205']]" mode="#all">
	<xsl:text>V̇O</xsl:text><emphasis style="inf">2</emphasis><!-- combining character: dot above (U+0307) -->
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if022-002-9780323340205']]" mode="#all">
	<xsl:text>V̇O</xsl:text><emphasis style="inf">2</emphasis><xsl:text>max</xsl:text><!-- combining character: dot above (U+0307) -->
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if022-003-9780323340205']]" mode="#all">
	<xsl:text>V̇/Q̇</xsl:text><!-- combining character × 2: dot above (U+0307) -->
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if022-004-9780323340205']]" mode="#all">
	<xsl:text>V̇</xsl:text><emphasis style="inf">D</emphasis><!-- combining character: dot above (U+0307) -->
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if022-005-9780323340205']]" mode="#all">
	<xsl:text>V̇</xsl:text><emphasis style="inf">E</emphasis><!-- combining character: dot above (U+0307) -->
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if022-006-9780323340205']]" mode="#all">
	<xsl:text>V̇</xsl:text><emphasis style="inf">T</emphasis><!-- combining character: dot above (U+0307) -->
</xsl:template>
<xsl:template match="ce:inline-figure[ce:link[@locator='if024-001-9780323340205']]" mode="#all">
	<emphasis style="obar">X</emphasis>
</xsl:template>
<!-- END special characters -->


<!-- intra-ref are links between XML files, cross-ref within the file; slower when combined, but easier to manage -->
<xsl:template match="ce:cross-ref|ce:intra-ref" mode="#all">
	<xsl:variable name="pii">
		<xsl:choose>
			<xsl:when test="local-name()='cross-ref'">
				<xsl:value-of select="ancestor::bk:chapter/bk:info/ce:pii"/>
			</xsl:when>
			<!-- intra-ref-->
			<xsl:otherwise>
				<xsl:value-of select="substring-before(substring-after(@xlink:href, 'pii:'), '#')"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>
	<xsl:variable name="refid">
		<xsl:choose>
			<xsl:when test="local-name()='cross-ref'">
				<xsl:value-of select="@refid"/>
			</xsl:when>
			<!-- intra-ref-->
			<xsl:otherwise>
				<xsl:value-of select="substring-after(@xlink:href, '#')"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:variable>

	<xref>
		<xsl:attribute name="refid">
			<!-- to find cross-ref (1050): @refid=d0530
					
//ce:cross-ref[@refid='d0655']/ancestor::chapter//ce:def-term[@id='d0655']/concat('tra_', ancestor::chapter/ce:title, '_', ancestor-or-self::ce:def-term[not(ancestor::ce:description)]/@id, ancestor::ce:description[not(ancestor::ce:description)]/preceding-sibling::ce:def-term[1], '_', count(preceding::ce:def-term) + 1) -->
			<!-- to find intra-ref (3016): @xlink:href=pii:B978-0-323-10012-0.00016-7#d3770
					//chapter[info/ce:pii/text()='B978-0-323-10012-0.00016-7']//ce:def-term[@id='d3770']/ancestor-or-self::ce:def-description[not(ancestor::ce:def-description)]/preceding-sibling::ce:def-term[1] -->

			<!-- this is inefficient and possibly memory-intensive to split into two selectors... -->
			<xsl:choose>
				<!-- output chapter title + target's ID -->
				<xsl:when test="$devmode='refid'">
					<xsl:value-of select="//bk:chapter[bk:info/ce:pii/text()=$pii]//ce:def-term[@id=$refid]/concat('tra_', ancestor::bk:chapter/ce:title, '_', ancestor-or-self::ce:def-term[not(ancestor::ce:def-description)]/@id, ancestor::ce:def-description[not(ancestor::ce:def-description)]/preceding-sibling::ce:def-term[1]/@id)"/>
				</xsl:when>

				<!-- use an entity_id instead of an ID signifier that would need a quickfix in METIS to complete -->
				<xsl:when test="$refidcalc=true()">
					<xsl:call-template name="set_atom_id">
						<xsl:with-param name="find_node" select="//bk:chapter[bk:info/ce:pii/text()=$pii]//ce:def-term[@id=$refid]"/>
					</xsl:call-template>
				</xsl:when>
				
				<!-- output strippedmainword only -->
				<xsl:otherwise>
					<xsl:value-of select="//bk:chapter[bk:info/ce:pii/text()=$pii]//ce:def-term[@id=$refid]/concat('tra_',  lower-case(replace(ancestor-or-self::ce:def-term[not(ancestor::ce:def-description)]/., '[^A-Za-z0-9]', '')), lower-case(replace(ancestor::ce:def-description[not(ancestor::ce:def-description)]/preceding-sibling::ce:def-term[1]/., '[^A-Za-z0-9]', '')))"/>
				</xsl:otherwise>
			</xsl:choose>

			<!-- ...but it's the quickest way to select the identifier when looking at a sub-entry instead of main -->
			<xsl:value-of select="//bk:chapter[bk:info/ce:pii/text()=$pii]//ce:def-term[@id=$refid and ancestor::ce:def-description]/concat('#', count(preceding::ce:def-term) + 1)"/>
		</xsl:attribute>
		<xsl:apply-templates mode="#current"/>
	</xref>
</xsl:template>



<!-- globally-used figure builder -->
<!-- ELEMENT component 	(label?, comp-title?, (file | component)+, caption?, credit?) -->
<xsl:template name="figure">
	<component type="figure">
		<!-- usually @id is copied, this enforces required presence and uniqueness -->
		<xsl:call-template name="id_generator">
			<xsl:with-param name="nodename" select="'figure'"/>
		</xsl:call-template>
		<xsl:if test="$devmode = 'Y'"><xsl:attribute name="id_legacy" select="@id"/></xsl:if>
		<xsl:if test="ancestor::ce:para[@view='extended']"><xsl:attribute name="availability" select="'electronic'"/></xsl:if>

		<!-- DTD prescribes the order these must appear -->
		<xsl:apply-templates select="ce:caption/ce:bold[not(preceding-sibling::text())]" mode="figlabel"/>
		<xsl:apply-templates select="ce:link"/>
		<xsl:apply-templates select="ce:caption"/>
		<xsl:apply-templates select="ce:source"/>
		
	</component>
</xsl:template>

<!-- the label is marked up in a b element and ends in a colon and needs to be placed outside caption -->
<xsl:template match="ce:bold[contains(., ':')]" mode="figlabel">
	<label><xsl:apply-templates/></label>
</xsl:template>

<!-- builds required file node when the @src isn't contained in source by using the @id -->
<xsl:template match="ce:link">
	<file src="{@locator}">
		<!--<xsl:attribute name="src" select="concat('_REPLACE_LOCATION__', @locator)"/>-->
	</file>
</xsl:template>

<xsl:template match="ce:simple-para" mode="#all">
	<xsl:apply-templates mode="#current"/>
</xsl:template>

<xsl:template match="ce:caption">
	<caption>
		<xsl:if test="$devmode = 'Y'"><xsl:attribute name="id_legacy" select="@id"/></xsl:if>
		<xsl:apply-templates mode="figcaption"/>
	</caption>
	<!-- these are really credit lines -->
	<xsl:apply-templates select="ce:simple-para[@role='caption' and not(preceding-sibling::*) and contains(., '(')]"/>
</xsl:template>

<!-- these are credit lines -->
<xsl:template match="ce:simple-para[@role='caption' and not(preceding-sibling::*)]" mode="figcaption"/>

<!-- but this is a caption -->
<xsl:template match="ce:simple-para[@role='caption' and not(preceding-sibling::*) and not(contains(., '('))]" mode="figcaption">
	<xsl:apply-templates mode="#current"/>
</xsl:template>

<!-- text of caption; removes trailing period -->
<xsl:template match="text()[ends-with(normalize-space(.), '.')]" mode="figcaption">
	<xsl:value-of select="substring(normalize-space(.), 1, (string-length(normalize-space(.)) - 1))"/>
</xsl:template>

<!-- strip out parens from source/credit -->
<xsl:template match="ce:source|ce:simple-para[@role='caption']">
	<credit>
		<xsl:value-of select="replace(normalize-space(.), '^\(?(.+)\)+$', '$1')"/>
	</credit>
</xsl:template>




<!-- pick up already-assigned entity_id or generate a new one -->
<xsl:template match="@id[parent::def-term[parent::def-list]]" name="set_atom_id">
	<!-- node to find an entity_id for if not the current one -->
	<xsl:param name="find_node" select="current()"/>

	<xsl:variable name="entity_id">
		<xsl:call-template name="get_entity_id">
			<xsl:with-param name="find_node" select="$find_node"/>
		</xsl:call-template>
	</xsl:variable>
	<!-- when called by name the context will be the html node rather than @id; this adapts for both situations -->
	<xsl:variable name="find_id" select="concat($find_node/ancestor::chapter/title, $find_node/ancestor::bk:chapter/ce:title, '::', $find_node/@id)"/>

	<xsl:attribute name="id">
		<!-- XML node ID prefix for use in both outputs -->
		<xsl:text>me</xsl:text>

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
		
		<!-- ids are required and must be unique, but we don't need a new entity_id in subentries so we add a simple iterating counter to it -->
		<xsl:if test="count($find_node/ancestor::def-term) > 0">
			<xsl:value-of select="concat('_', count($find_node/ancestor-or-self::def-term) - 1, '_', count($find_node/preceding-sibling::def-term) + 1)"/>
		</xsl:if>
	</xsl:attribute>
</xsl:template>


<!-- generates id attributes and populates with distinct values for new nodes requiring them -->
<xsl:template name="id_generator">
    <!-- force a new ID if "true" or set to a unique ID (eg para_splitter) -->
    <xsl:param name="new" select="'false'"/>
    <!-- selects local-name(), but can send new nodename if it will be changing and the match rule is complicated -->
    <xsl:param name="nodename" select="local-name()"/>
    <!-- METIS adds its own prefix to some elements; strip provided value -->
    <xsl:param name="strip_prefix" select="'false'"/>

    <xsl:variable name="prefix">
        <xsl:choose>
            <xsl:when test="$nodename = 'audio'">a</xsl:when>
			<xsl:when test="$nodename = 'def'">d</xsl:when>
			<xsl:when test="$nodename = 'entry'">me</xsl:when>
			<xsl:when test="$nodename = 'figure'">f</xsl:when>
            <xsl:when test="$nodename = 'form'">fm</xsl:when>
            <xsl:when test="$nodename = 'headw'">hw</xsl:when>
            <xsl:when test="$nodename = 'para'">p</xsl:when>
            <xsl:when test="$nodename = 'video'">v</xsl:when>

           
            <xsl:otherwise>NONE</xsl:otherwise>
        </xsl:choose>
    </xsl:variable>

    <xsl:if test="$prefix != 'NONE'">
        <xsl:attribute name="id">
            <xsl:choose>
                <!-- generate a brand new ID number -->
                <xsl:when test="$new != 'false' or not(@id)">
                    <xsl:value-of select="$prefix"/>
                    <xsl:choose>
                        <!-- iterate current ID by 1 if not already used; this is preferred for cleanliness of XML -->
                        <xsl:when test="($new = 'true' or $new = 'false') and @id and not(//*[@id = concat($prefix, number(substring-after(@id, $prefix) + 1))])">
                            <xsl:value-of select="format-number(number(substring-after(@id, $prefix) + 1), '0000')"/>
                        </xsl:when>
                        <!-- a unique ID was already generated, convert to numeric; this is used by para_splitter because the ID iterator would result in duplicates -->
                        <xsl:when test="$new != 'true' and $new != 'false'">
                            <xsl:value-of select="translate($new, 'abcdefghijklmnopqrstuvwxyz', '00000000000000000000000000')"/>
                        </xsl:when>
                        <!-- generate a unique ID based on the current, unique node and make it numeric -->
                        <xsl:otherwise>
                            <xsl:value-of select="translate(generate-id(.), 'abcdefghijklmnopqrstuvwxyz', '00000000000000000000000000')"/>
                        </xsl:otherwise>
                    </xsl:choose>
                </xsl:when>
                <!-- use existing ID after stripping off temporary prefix -->
                <xsl:when test="@id and $strip_prefix != 'false'">
                    <xsl:value-of select="substring-after(@id, $strip_prefix)"/>
                </xsl:when>
                <!-- use existing ID -->
                <xsl:when test="@id">
                    <xsl:value-of select="@id"/>
                </xsl:when>
                <!-- um, whoops -->
                <xsl:otherwise>ERROR</xsl:otherwise>
            </xsl:choose>
        
        </xsl:attribute>
    </xsl:if>

</xsl:template>








<!-- copies every element along with its attributes, applying above templates as required; devmode highlights missed elements -->
<xsl:template match="*" mode="entry">
	<missed class="devmode">
	<xsl:copy>
		<xsl:copy-of select="@*"/>
		<xsl:if test="$devmode = 'missed'">
			<xsl:attribute name="devmode"><xsl:value-of select="name()"/></xsl:attribute>
			<xsl:attribute name="templatemode">entry</xsl:attribute>
		</xsl:if>
		<xsl:apply-templates select="node()"/>
	</xsl:copy>
	</missed>
</xsl:template>


</xsl:stylesheet>