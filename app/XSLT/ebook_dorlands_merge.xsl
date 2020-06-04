<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE dictionary SYSTEM "https://major-tool-development.s3.amazonaws.com/DTDs/Dictionary_4_8.dtd">
<!-- https://major-tool-development.s3.amazonaws.com/DTDs/Dictionary_4_8.dtd -->

<xsl:stylesheet version="3.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
<!--
	merges Dorland's Main and Pocket into single XML

	2020-01-24 JWS	original
	2020-06-02 PJ/JWS	updated to emit xref/@refid and to work with Saxon

TODO: update these instructions; put DTD on S3 so the correct URL can be used

	0. be sure your XSL transformation tool supports XSLT 3
	1. replace the source XML's DOCTYPE with the DOCTYPE from this XSLT. This removes the unfollowable reference to the old DTD and adds support for known entities
		a. life will also be easier if you combine all question XML files into a single file
	2. clean up source XML
		a. add new named entities to DOCTYPE to be replaced with characters (eg &ndash; is en dash: –); decimal entities do not require this
		b. fix well-formedness errors
		c. fix other non-standard, unwanted characters (eg, line separator) and add any additional content needed (eg, tables). UUID stability is dependent on the number of characters
	2b. Insert/update the location of the pocket xml file below under "the location of the Pocket XML to merge"
	3. transform the source XML against this XSLT
		a. note setting of $devmode param
		b. note setting of $isbn param
		c. note settings of $uuid_* params and $entity_id_base; recommend changing $uuid_* to new random values for a new title to help ensure uniqueness
			REQUIRED: Each dtd requires changes to: (TEMPLATES: generateUUID:uniquenode) (VARIABLES: entity_id_base, uuid_tree:atoms) (OTHER: how the ID assignment is concatted)
			REQUIRED: Each product requires changes to (PARAMS: uuid_multiplier, uuid_increment, uuid_divisor) 
		d. verify output does not contain ERROR text (eg, para[@type=special-item]/@role)
	4. save transformed XML in a batch directory (batch_YYYY-MM-DD) following this naming convention letter_a-z_4e.xml (lowercase and edition included)
		a. the main Dictionary DTD needs to be followable to ensure the output is valid. Default location is https://major-tool-development.s3.amazonaws.com/DTDs/Dictionary_4_5.dtd and can be changed in this XSLT
	5. ZIP batch directory and upload to converted_XML bucket in project's bucket on S3
-->

<!-- set indent to "no" for maximum speed/testing, final output should have indents/pretty which can be done post processing in XMLspy etc. -->
<xsl:output method="xml" encoding="utf-8" indent="yes"
 omit-xml-declaration="yes"
 doctype-public="-//ES//DTD dictionary DTD version 1.0//EN//XML" doctype-system="Dictionary_4_8.dtd"
 media-type="text/html"/><!-- https://major-tool-development.s3.amazonaws.com/DTDs/Dictionary_4_8.dtd -->

<xsl:preserve-space elements="br category option"/>



<!-- Do not change the values of params in this document. Set them when running the transformation -->

<xsl:param name="isbn" select="'0000000000000'"/> <!-- the source book's ISBN, 9781455756438 is the latest DMD print ISBN, (pocket has another ISBN)  -->
<xsl:param name="devmode" select="'N'"/>	<!-- set to Y to: 1) skip counting characters for $entity_id_base 2) generate only 10 UUIDs 3) avoid the expense of calculating the def/@n -->
<xsl:param name="output_tree" select="'false'"/> <!-- set to true to output the $uuid_tree fragment used for key generation when ALSO in devmode=Y -->
<!-- Set new values for new titles!!  Used to calculate the UUIDs, use the same values for each transformation of a single title to ensure the same values are calculated each time. 1-100000 -->
	<xsl:param name="uuid_multiplier" select="00000"/>
	<xsl:param name="uuid_increment" select="00001"/>
	<xsl:param name="uuid_divisor" select="00002"/>

<xsl:param name="existing_entityid_file" select="'PRESERVE'"/> <!-- the location of the file containing the entity_ids already assigned in METIS; set to NONE if they have not been assigned externally; set to PRESERVE to use existing entity_id -->

<xsl:variable name="entityids" select="document(concat('file:///', translate($existing_entityid_file, '\', '/')))" as="document-node()"/>


<xsl:param name="pocket_xml_file" select="'c:\DorlandsDictionary_DPD_ALL.xml'"/> <!-- the location of the Pocket XML to merge -->

<xsl:variable name="pocket_entries" select="document(concat('file:///', translate($pocket_xml_file, '\', '/')))" as="document-node()"/>

<xsl:key name="headw_id" match="//alpha/entry" use="headw/@id"/>




<!-- find assigned entity_id in $entityids or preserve existing assignment -->
<xsl:template name="get_entity_id">
	<!-- node to find an entity_id for if not the current one -->
	<xsl:param name="find_node" select="current()"/>
	
	<xsl:choose>
		<!-- PRESERVE existing entity_id assignment from XML is preferred; do not preserve group/@id as entity_id -->
		<xsl:when test="$existing_entityid_file = 'PRESERVE' and (($find_node/local-name() != 'group' and $find_node/@id != '') or ($find_node/self::attribute() and $find_node != ''))">
			<xsl:value-of select="$find_node/@id | $find_node"/>
		</xsl:when>
		
		<!-- the $existing_entity_id_file is defined and should be checked for an assignment -->
		<xsl:when test="$existing_entityid_file != 'NONE' and $existing_entityid_file != 'PRESERVE'">
			<xsl:variable name="temp" select="concat($find_node/ancestor::alpha/@letter, '::', $find_node/@id)"/>
			<xsl:value-of select="$entityids/key('metisID',$temp)/@entity_id"/>
		</xsl:when>

		<xsl:otherwise>
			<!-- this will force ID generation -->
		</xsl:otherwise>
	</xsl:choose>
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
			<xsl:with-param name="atoms" select="//alpha/entry"/>
			<xsl:with-param name="counter" select="1"/>
		</xsl:call-template>
	</uuids>
</xsl:variable>

<!-- keys used to improve the efficiency of applying UUIDs to atoms -->
<!-- NOTE: if document has a default namespace, this will have to use it -->
<xsl:key name="uuid_key" match="$uuid_tree//atom" use="@genid"/>
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
		<xsl:attribute name="uniquenode" select="concat($atoms/ancestor::alpha/@letter, '::', $atoms[position()=1]/headw/@id)"/>	<!-- a distinct per atom, omnipresent node to test pairing source atoms with generated IDs; here it is combined with letter to support potentially non-unique headw/@id and calling set_atom_id -->
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



<!-- inject ISBN -->
<xsl:template match="dictionary">
	<!-- document the random strings used to generate output UUIDs -->
	<xsl:comment> uuid_multiplier: <xsl:value-of select="$uuid_multiplier"/> </xsl:comment><xsl:value-of select="$newline"/>
	<xsl:comment> uuid_increment: <xsl:value-of select="$uuid_increment"/> </xsl:comment><xsl:value-of select="$newline"/>
	<xsl:comment> uuid_divisor: <xsl:value-of select="$uuid_divisor"/> </xsl:comment><xsl:value-of select="$newline"/>
	<xsl:value-of select="$newline"/>

	<!-- outputs contents of tree fragment used for key generation when $devmode=Y and $output_tree=true-->
	<xsl:if test="$devmode='Y' and $output_tree='true'">
		<xsl:copy-of select="$uuid_tree"/>
	</xsl:if>
	
	<dictionary isbn="{$isbn}">
		<xsl:apply-templates/>
	</dictionary>
</xsl:template>


<!-- selects all of MAIN and merges matching POCKET into these, then adds unique POCKET at end -->
<xsl:template match="alpha">
	<alpha>
		<xsl:apply-templates select="@* | node()"/>
		
		<xsl:comment>unique POCKET from this alpha content starts here</xsl:comment>
		<xsl:apply-templates select="$pocket_entries//alpha[@letter = current()/@letter]/entry[not(headw/@id = current()/entry/headw/@id)]"/>
	</alpha>
</xsl:template>

<!-- MAIN entries -->
<xsl:template match="entry">
	<entry>
		<xsl:apply-templates select="@* | node()[local-name() != 'component'][local-name() != 'table'][local-name() != 'entry']"/>

		<!-- merges matching POCKET from *any alpha* into MAIN -->
		<xsl:apply-templates select="$pocket_entries/dictionary/body/alpha/entry[headw/@id = current()/headw/@id]/defgroup"/>
		
		<!-- DTD requires these be located after defgroup -->
		<xsl:apply-templates select="component | table | entry"/>
		
	</entry>

</xsl:template>

<!-- unique POCKET entries -->
<xsl:template match="entry[@type='5']">
	<entry>
		<xsl:apply-templates select="@* | node()"/>
	</entry>

</xsl:template>

<!-- atom-level group will need entity_id assigned -->
<xsl:template match="group[parent::alpha]">
	<group>
		<xsl:apply-templates select="@*"/>
		<xsl:attribute name="id">
			<xsl:call-template name="set_atom_id"/>
		</xsl:attribute>
		
		<xsl:apply-templates select="node()"/>
	</group>

</xsl:template>

<!-- transforms type ID from database into text -->
<xsl:template match="@type">
	<xsl:attribute name="type">
		<xsl:choose>
			<!-- both = entry is used for both MAIN and POCKET -->
			<xsl:when test=".='4' and (parent::entry and $pocket_entries/dictionary/body/alpha/entry[headw/@id = current()/parent::entry/headw/@id])">
				<xsl:text>shared</xsl:text>
			</xsl:when>
			
			<!-- content only used in MAIN -->
			<xsl:when test=".='4' and (parent::entry or parent::defgroup)">
				<xsl:text>main</xsl:text>
			</xsl:when>
			
			<!-- content only used in POCKET -->
			<xsl:when test=".='5' and (parent::entry or parent::defgroup)">
				<xsl:text>pocket</xsl:text>
			</xsl:when>
			
			<!-- could be anything, but is probably an error -->
			<xsl:otherwise>
				<xsl:if test="$devmode != 'N'">
					<xsl:text>ERROR: </xsl:text>
				</xsl:if>
				<xsl:value-of select="."/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:attribute>
</xsl:template>



<!-- add prefix -->
<xsl:template match="@id[parent::headw]">
	<xsl:attribute name="id" select="concat('hw', .)"/>

</xsl:template>

<!-- category is complete and correct from source XML -->
<xsl:template match="category">
	<category>
		<xsl:attribute name="cat_id" select="concat('cat', @cat_id)"/>
		<xsl:apply-templates/>
	</category>

</xsl:template>

<!-- add prefix -->
<xsl:template match="@id[parent::def]">
	<xsl:attribute name="id" select="concat('d', .)"/>

</xsl:template>

<!-- resets @n with an accurate count of its order; allows ignoring of defnum -->
<!-- $devmode=Y will avoid calculating this to improve speed -->
<xsl:template match="@n[parent::def][$devmode != 'Y']">
	<!-- NOT ACCURATE: <xsl:attribute name="n" select="count(preceding::def[following-sibling::def = current()/parent::def]) + 1"/>-->
	<!-- SEEMS NO FASTER: <xsl:attribute name="n" select="count(preceding::def[ancestor::defgroup = current()/ancestor::defgroup]) + 1"/>-->
	<!-- NOT ACCURATE: <xsl:attribute name="n" select="count(preceding::def[parent::defgroup = current()/ancestor::defgroup and following-sibling::def = current()/parent::def]) + 1"/>-->
	<xsl:attribute name="n" select="count(current()/ancestor::def/preceding-sibling::def) + 1"/>

</xsl:template>


<!-- ignore pocket defgroups containing no real content (only punc) -->
<xsl:template match="defgroup[@type='pocket'][not(def or sub-def)]"/>

<!-- ignore defnum and instead populate only def/@n -->
<xsl:template match="defnum"/>

<!-- @hword_id is now @refid with "a:" prefix; @id, @xref_id are omitted -->
<xsl:template match="xref">
	<!-- node to find an entity_id for if not the current one; in practice xref always targets a headw -->
	<xsl:param name="find_node" select="//headw[@id = current()/@refid]/ancestor::entry[parent::alpha]/@id"/>

	<!-- returns an entity_id if one was already assigned, null otherwise -->
	<xsl:variable name="entity_id">
		<xsl:call-template name="get_entity_id">
			<xsl:with-param name="find_node" select="$find_node"/>
		</xsl:call-template>
	</xsl:variable>
	<!-- used as unique atom identifier for which a new entity_id was calculated -->
	<xsl:variable name="find_id" select="concat($find_node/ancestor::alpha/@letter, '::', $find_node/parent::entry/headw[1]/@id)"/>

    <xref refid="a:{@hword_id}">
		<xsl:attribute name="refid">
			<!-- XML node ID prefix for use in both outputs -->
			<xsl:text>a:</xsl:text>
	
			<xsl:choose>
				<!-- useful whenever you want to preserve entity IDs from previously ingested XML; pull from db and put into external XML file --> 
				<xsl:when test="$entity_id != ''">
					<xsl:value-of select="$entity_id"/>
				</xsl:when>
				<!-- typical ID generation: XML node ID prefix + unique ID base for document + generated-id for atom + output of UUID process on atom -->
				<xsl:otherwise>
					<xsl:value-of select="concat('', format-number($entity_id_base, '9999999'), translate($uuid_tree/key('uuid_uniquenode', $find_id)[position()=1]/@genid, 'ghijklmnopqrstuvwxyz', '0123456789abcdef0123'), $uuid_tree/key('uuid_uniquenode', $find_id)[position()=1]/@uuid)"/>
				</xsl:otherwise>
			</xsl:choose>
			<!-- append the targeted ID -->
			<xsl:text>#</xsl:text>
			<xsl:call-template name="id_generator">
				<xsl:with-param name="new" select="@refid"/>
				<xsl:with-param name="nodename" select="'headw'"/>
			</xsl:call-template>
		</xsl:attribute>

        <xsl:apply-templates/>
    </xref>
</xsl:template>

<!-- figure, audio, video are all component now; order of children is enforced -->
<xsl:template match="figure">
	<component type="figure" id="f{@id}">
		<xsl:apply-templates select="img"/>
		<xsl:apply-templates select="fig-title"/>
		<xsl:apply-templates select="credit"/>
	</component>

</xsl:template>

<!-- img renamed to file -->
<xsl:template match="img">
	<file src="{substring-before(@src, '.')}"/>

</xsl:template>

<!-- fig-title renamed to caption -->
<xsl:template match="fig-title">
	<caption>
		<xsl:apply-templates/>
	</caption>

</xsl:template>



<!-- BEGIN transform tables to table_1_0.dtd -->
<xsl:template match="table/@id">
	<xsl:attribute name="id">
		<xsl:value-of select="concat('t', count(preceding-sibling::table) + 1)"/>
	</xsl:attribute>
</xsl:template>

<xsl:template match="tgroup">
	<tgroup>
		<xsl:copy-of select="@*"/>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('tg', '_REPLACE_ME__')"/>
		</xsl:attribute>
		<xsl:call-template name="colspec"/>
		
		<xsl:apply-templates/>
	</tgroup>
</xsl:template>

<!-- builds colspec for tgroup because DTD requires it and source XML lacks it -->
<xsl:template name="colspec">
	<xsl:param name="counter" select="1"/>

	<colspec>
		<xsl:attribute name="colname" select="concat('col', $counter)"/>
		<xsl:attribute name="colnum" select="$counter"/>
	</colspec>
	<xsl:if test="$counter &lt; @cols">
		<xsl:call-template name="colspec">
			<xsl:with-param name="counter" select="$counter + 1"/>
		</xsl:call-template>
	</xsl:if>
</xsl:template>

<xsl:template match="row">
	<row>
		<xsl:copy-of select="@*"/>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('tr', '_REPLACE_ME__')"/>
		</xsl:attribute>
		<xsl:apply-templates/>
	</row>
</xsl:template>

<xsl:template match="entry[ancestor::table]">
	<cell>
		<xsl:apply-templates select="@*|node()" mode="#current"/>
	</cell>
</xsl:template>

<!-- letting the importer number these is probably OK -->
<xsl:template match="para[ancestor::table]">
	<para>
		<xsl:copy-of select="@*"/>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('p', '_REPLACE_ME__')"/>
		</xsl:attribute>
		<xsl:apply-templates/>
	</para>
</xsl:template>
<!-- END tables -->


<!-- Mosby's used these as "things that are less than a definition" rather than "this is a sub of a definition" and the element name is unclear -->
<xsl:template match="sub-def[not(preceding-sibling::sub-def)]">
	<para type="special-item">
		<xsl:attribute name="id" select="concat('p', '_REPLACE_ME__')"/>
		<xsl:attribute name="role">
			<!-- counts from pocket source
			indications, contraindications, and adverse effects (for drug entries),
				Indications 913
				Contraindications 912
				Adverse Effects 834
			
			observations, interventions, and nursing considerations (for disease entries),
				Observations 317
				Interventions (310/364)
				Patient Care Considerations (290/311)
			
			method, nursing interventions, and outcome criteria (for procedure entries).
				Method 71
				Interventions (48/364)
				Patient Care Considerations (18/311)
				Outcome Criteria 71-->
			<xsl:choose>
				<!-- Indications, Contraindications, Adverse Effects -->
				<xsl:when test="sub-head[.='Indications' or .='Contraindications']">drug</xsl:when>
				<!-- Observations, Interventions, Patient Care Considerations -->
				<xsl:when test="sub-head[.='Observations']">disease</xsl:when>
				<!-- Method, Interventions, Patient Care Considerations, Outcome Criteria -->
				<xsl:when test="sub-head[.='Method' or .='Outcome Criteria'] | following-sibling::sub-def/sub-head[.='Outcome Criteria']">procedure</xsl:when>
				<!-- the above subheads clearly indicate a @role for the set, some subheads appear in multiple sets and require manual @role determination in output; also catches non-standardized subheads -->
				<!-- Adverse Effects, Interventions, Patient Care Considerations -->
				<xsl:otherwise>ERROR</xsl:otherwise>
			</xsl:choose>
		</xsl:attribute>
		<para>
			<xsl:attribute name="id" select="concat('p', '_REPLACE_ME__')"/>
			<xsl:attribute name="role" select="translate(lower-case(sub-head), ' ', '_')"/>
			<xsl:apply-templates select="*[not(local-name() = 'sub-head')]|text()[normalize-space()]"/>
		</para>
		<xsl:apply-templates select="following-sibling::sub-def" mode="special-item"/>
	</para>
</xsl:template>

<xsl:template match="sub-def[preceding-sibling::sub-def]"/>

<xsl:template match="sub-def[preceding-sibling::sub-def]" mode="special-item">
	<para>
		<xsl:attribute name="id" select="concat('p', '_REPLACE_ME__')"/>
		<xsl:attribute name="role" select="translate(lower-case(sub-head), ' ', '_')"/>
		<xsl:apply-templates select="*[not(local-name() = 'sub-head')]|text()[normalize-space()]"/>
	</para>


</xsl:template>

<!-- transformed to para/@role -->
<xsl:template match="sub-head"/>

<!-- merge adjacent emphasis nodes with identical styles; aside from cleaning markup, this shortens headw to fit in database where title is limited to 255 characters -->
<!-- two patterns are present in source: one in which individual characters are separately styled and separated by newlines and a second in which whole words are separately styled and typically separated by a space -->
<xsl:template match="headw[emphasis] | def[emphasis]">
	<xsl:copy>
		<xsl:apply-templates select="@*"/>
		<xsl:for-each-group select="node() except text()[not(normalize-space())][. != ' ']" group-adjacent="boolean(self::*) or .=' '">
		  <xsl:choose>
			<xsl:when test="current-grouping-key() and name()='emphasis'">
			  <xsl:for-each-group select="current-group()" group-by="concat(node-name(.), '|', @style)">
				<!-- text nodes are included here -->
				<xsl:if test="name()='emphasis'">
					<xsl:element name="{name()}" namespace="{namespace-uri()}">
						<xsl:copy-of select="@style"/>
						<xsl:apply-templates select="current-group()" mode="mergingstyles"/>
					</xsl:element>
				</xsl:if>

			  </xsl:for-each-group>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates select="current-group()"/>
			</xsl:otherwise>
		  </xsl:choose>
		 </xsl:for-each-group>
	</xsl:copy>
</xsl:template>

<!-- combines adjacent emphasis -->
<xsl:template match="node()" mode="mergingstyles">
	<!-- reinserts spaces where needed; newlines only separate adjacent nodes that should not contain spaces once merged, we want to reinsert all the rest to maintain proper spacing -->
	<xsl:if test="position() &gt; 1 and preceding-sibling::text()[1] != $newline">
		<xsl:value-of select="preceding-sibling::text()[1]"/>
	</xsl:if>
	<xsl:apply-templates select="node()"/>
</xsl:template>

<!-- pick up already-assigned entity_id or generate a new one -->
<xsl:template match="@id[parent::entry[parent::alpha]]" name="set_atom_id">
	<!-- node to find an entity_id for if not the current one -->
	<xsl:param name="find_node" select="current()"/>

	<xsl:variable name="entity_id">
		<xsl:call-template name="get_entity_id">
			<xsl:with-param name="find_node" select="$find_node"/>
		</xsl:call-template>
	</xsl:variable>
	<!-- when called by name the context will be the html node rather than @id; this adapts for both situations -->
	<xsl:variable name="find_id" select="concat($find_node/ancestor::alpha/@letter, '::', $find_node/parent::entry/headw[1]/@id)"/>

	<xsl:attribute name="id">
		<!-- XML node ID prefix for use in both outputs -->
		<xsl:text>me</xsl:text>

		<xsl:choose>
			<!-- useful whenever you want to preserve entity IDs from previously ingested XML; pull from db and put into external XML file --> 
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
                        <xsl:when test="($new = 'true' or $new = 'false') and @id and not(//*[@id = concat($prefix, number(substring-after(@id, $prefix)) + 1)])">
                            <xsl:value-of select="format-number(number(substring-after(@id, $prefix)) + 1, '0000')"/>
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






<!-- default copy rules for everything else -->
<xsl:template match="node()">
	<xsl:copy>
		<xsl:apply-templates select="@*" mode="#current"/>
		<xsl:apply-templates mode="#current"/>
	</xsl:copy>
</xsl:template>

<xsl:template match="@*">
	<xsl:copy-of select="."/>
</xsl:template>



</xsl:stylesheet>