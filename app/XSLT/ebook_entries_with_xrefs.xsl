<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:fo="http://www.w3.org/1999/XSL/Format"
	xmlns:xs="http://www.w3.org/2001/XMLSchema"
	xmlns:fn="http://www.w3.org/2005/xpath-functions"
	exclude-result-prefixes="xsl fo xs fn"
>
<!--
Extracts just those Dictionary DTD entry elements containing an xref as a descendant. Meant to fix missing xrefs in Veterinary Dictionary. Will also populate entity_ids if provided in an external file
2018-01-12 JWS	original
2018-01-31 JWS	adds entity_id lookup to headw and xref enables toggling of replacements from external entities list; THIS IS INTENSIVE and testing should be performed on severely limited dataset



	0. be sure your XSL transformation tool supports XSLT 2
	1. replace the source XML's DOCTYPE with the DOCTYPE from this XSLT. This removes the unfollowable reference to the old DTD and adds support for known entities
	2. build an external entities file if you'd like to use one, set as $entity_xml_file
		a. comment out the variable setting $entities if you don't need this
		b. check that $alpha_title_find and $alpha_title_replace match the alpha_title replacements used in atoms for this title
	3. transform the source XML against this XSLT
		a. add any missing entities to both the source XML and this XSLT
		b. set $entity_replace and $xref_replace to FALSE if you don't need this step
	4. if me_REPLACE_ME__ exists in output, something is wrong and should be fixable; if tra_collapsedterm exists in output, something might not be findable and might be fixable
	5. save transformed XML in a batch directory following this naming convention letter_a-z_4e.xml (lowercase and edition included)
		a. the main Dictionary DTD needs to be followable to ensure the output is valid. Default location is Y:\WWW1\METIS\Dictionary_4_4.dtd and can be changed in this XSLT
	6. ZIP batch directory and upload to converted_XML bucket in project's bucket on S3

-->


<xsl:output method="xml" encoding="utf-8" indent="yes"
 omit-xml-declaration="yes"
 doctype-public="-//ES//DTD dictionary DTD version 1.0//EN//XML" doctype-system="Y:\WWW1\METIS\Dictionary_4_4.dtd"
 media-type="text/html"/>

<xsl:preserve-space elements="br"/>

<xsl:param name="entity_replace" select="'TRUE'"/> <!-- set to FALSE to prevent replacing these; will use 'hw_REPLACE_ME__' if not found -->
<xsl:param name="xref_replace" select="'TRUE'"/> <!-- set to FALSE to prevent replacing these; will use 'tra__REFID__' if not found -->
<xsl:param name="entity_xml_file" select="'c:\temp\delete\studdert xml\batch_2018_01_12\letter_a-z_entities_4e.xml'"/> <!-- the location of the file containing the alpha_title:entity_id replacement sets -->
<xsl:param name="alpha_title_find" select="'αβγδηθκλξρμνζχψωΑΒΓΔΖ'"/> <!-- alpha_title sometimes normalizes characters, replacing these XML characters with something simpler for alphabetization -->
<xsl:param name="alpha_title_replace" select="'aßgdetklxrµnzxpoABGDZ'"/> <!-- this is what alpha_title contains -->


<!-- this is a file containing values from existing database used to replace strings in this XML; it is of the format below and pairs atoms..alpha_title with atoms..entity_id;
   @noxref is used to exclude headw with identical alpha_text that do not have an xref (eg, "S" and "Σ" both have alpha_title="S"), this will throw transformation errors if not removed
<entities>
	<set find="angioneurosis" replace="59286faf31177630178167"/>
	<set find="angioneurosis" replace="SOMEOTHERENTITYID" noxref="true"/>
</entities>
-->
<xsl:variable name="entities" select="document($entity_xml_file)" as="document-node()"/>

<xsl:key name="entity_sets" match="set[not(@noxref='true')]" use="@find"/>


<xsl:template match="dictionary">
<dictionary>
	<xsl:apply-templates/>
</dictionary>
</xsl:template>

<xsl:template match="body">
<body>
	<xsl:apply-templates/>
</body>
</xsl:template>


<xsl:template match="alpha">
<alpha>
	<xsl:attribute name="letter" select="@letter"/>
	<xsl:apply-templates select="entry[descendant-or-self::xref]"/>
</alpha>
</xsl:template>



<!-- simple deep copy; skips all further processing... -->
<xsl:template match="entry">
	<xsl:copy-of select="."/>
</xsl:template>

<!-- ...OR replace entry or xref with entities -->
<xsl:template match="entry[parent::alpha and ($entity_replace='TRUE' or $xref_replace='TRUE')]">
	<entry>
		<xsl:attribute name="id">
			<xsl:call-template name="entity_lookup">
				<xsl:with-param name="headw" select="headw"/>
				<xsl:with-param name="prefix" select="'me'"/>
				<xsl:with-param name="default" select="'_REPLACE_ME__'"/><!-- if these remain in output it is an indicator something is wrong -->
			</xsl:call-template>
		</xsl:attribute>
		<xsl:apply-templates select="node()|@*" mode="entities"/>
	</entry>
</xsl:template>



<!-- replace xrefs with entities where possible or with tra_collapsedname where it is not -->
<xsl:template match="@refid[$xref_replace='TRUE' and .='tra__REFID__']" mode="entities">
	<xsl:attribute name="refid">
		<xsl:call-template name="entity_lookup">
			<xsl:with-param name="headw" select="current()/parent::*"/>
			<xsl:with-param name="prefix" select="'a:'"/>
			<xsl:with-param name="default" select="concat('tra_',  lower-case(replace(string-join(current()/parent::*//text()[normalize-space()], ''), '[^A-Za-z0-9]', '')))"/>
		</xsl:call-template>
	</xsl:attribute>
</xsl:template>


<!-- default copy rules for entity_replace and xref_replace processes -->
<xsl:template match="node()" mode="entities">
	<xsl:copy>
		<xsl:apply-templates select="@*" mode="#current"/>
		<xsl:apply-templates mode="#current"/>
	</xsl:copy>
</xsl:template>

<xsl:template match="@*" mode="entities">
	<xsl:copy-of select="."/>
</xsl:template>




<!-- looks up entities in the key generated from the external entities list and replaces if found or uses $default if not; headw in entity_sets uses atoms..alpha_title and params set above determine the character normalization process -->
<xsl:template name="entity_lookup">
	<xsl:param name="headw"/>
	<xsl:param name="prefix" select="''"/>
	<xsl:param name="default"/>

	<xsl:variable name="entity_id" select="$entities/key('entity_sets', translate(string-join($headw//text()[normalize-space()], ''), $alpha_title_find, $alpha_title_replace))/@replace"/>

	<xsl:choose>
		<!-- if an error is thrown on this line due to having the wrong number of occurrence to match the sequence type use noxref="true" in the entity_sets file to exclude one or more non-unique alpha_text -->
		<xsl:when test="boolean($entity_id)"><xsl:value-of select="concat($prefix, $entity_id)"/></xsl:when>
		<xsl:otherwise><xsl:value-of select="concat($prefix, $default)"/></xsl:otherwise>
	</xsl:choose>

</xsl:template>


</xsl:stylesheet>
