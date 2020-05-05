<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="3.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fo="http://www.w3.org/1999/XSL/Format" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:fn="http://www.w3.org/2005/xpath-functions"
				xmlns:bk="http://www.elsevier.com/xml/bk/dtd"
				xmlns:html="http://www.w3.org/1999/xhtml"
				xmlns:aid="http://ns.adobe.com/AdobeInDesign/4.0/"
				xmlns:sb="http://www.elsevier.com/xml/common/struct-bib/dtd"
				xmlns:ce="http://www.elsevier.com/xml/common/dtd"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				xmlns:mml="http://www.w3.org/Math/DTD/mathml2/mathml2.dtd"
				exclude-result-prefixes="bk html aid sb ce xlink mml fo xs fn">
	<!-- Book.dtd word/page and character/page count STEP 1: Create nodes to count
	2016-09-28 JWS	original
	2018-08-31 JWS	added support for pagebreak.xml

	1. Add non-XSL namespaces from above (ie, bk, html, etc) to input XML document's root note (chapter); remove unnamed namespace if present; remove DOCTYPE; specify location of pagebreak.xml if ce:anchor elements are not present
		a. do NOT pretty-print source XML if using pagebreak.xml because that will add countable text nodes
		b. pagebreak.xml will also need DOCTYPE removed unless the DTD is locally available (a copy of a basic pagebreak500.dtd can be used); the error returned otherwise will misleadingly be that XMLSpy cannot find pagebreak.xml
	2. Run this first in XMLSpy. It creates countable nodes split on ce:anchor as a document which can then be counted using Step 2
	3. Run Step 2 on the output (no need to save)
	4. Paste final output into Excel
	 -->


<xsl:output method="xml" encoding="utf-8" indent="no"
 omit-xml-declaration="no"/>

<xsl:param name="devmode" select="'N'"/> <!-- 'Y' will nodes for clarification -->

<xsl:param name="pagebreak_file" select="'NONE'"/> <!-- the relative location of the pagebreak.xml file, if any -->
<xsl:param name="pagebreak_electronic" select="'exclude'"/> <!-- default excludes e-only pages -->

<!-- reads pagebreak.xml file in as document to harvest its XPATH -->
<xsl:variable name="pagebreaks" select="document($pagebreak_file)" as="document-node()"/>

<!-- creates new document tree fragment, duplicating pagebreak.xml and adding IDs from current document using pagebreak.xml's XPATHs -->
<xsl:variable name="pagebreak_ids">
	<xsl:variable name="current" select="/"/>
	<xsl:for-each select="$pagebreaks//page">
		<xsl:if test="not($pagebreak_electronic = 'exclude') or not(contains(@name, 'e'))">
			<xsl:variable name="xpath" select="./@break-position"/>
			<xsl:variable name="pagebreak_id"><xsl:evaluate xpath="'generate-id(' || $xpath || ')'" context-item="$current"/></xsl:variable>
			<page name="{@name}" next="{@next}" break-position="{@break-position}" offset="{@offset}">
				<xsl:attribute name="document_id" select="$pagebreak_id"/>
			</page>
		</xsl:if>
	</xsl:for-each>
</xsl:variable>



<xsl:variable name="newline">
		<xsl:text>
</xsl:text>
</xsl:variable>



<!-- will contain all text split into page nodes in a variable suitable for counting -->
<xsl:variable name="pages" as="element()">
	<xsl:apply-templates select="." mode="pages"/>
</xsl:variable>

<xsl:template match="chapter" mode="pages">
	<root>
		<!-- displays pagebreaks with IDs for debugging -->
		<xsl:if test="$devmode = 'Y'">
			<xsl:copy-of select="$pagebreak_ids"/>
		</xsl:if>

		<page>
		<xsl:apply-templates mode="#current"/>
		</page>
	</root>

</xsl:template>

<!-- page markers found in XML -->
<xsl:template match="ce:anchor" mode="pages">
	<xsl:text disable-output-escaping="yes">&lt;/page&gt;</xsl:text>
	<anchor>
		<xsl:attribute name="id" select="@id"/>
	</anchor>
	<xsl:text disable-output-escaping="yes">&lt;page&gt;</xsl:text>
</xsl:template>



<!-- ignore these elements -->
<xsl:template match="info|ce:label|ce:display" mode="pages"/>

<!-- except we need page markers without text in some instances -->
<xsl:template match="ce:display[not($pagebreak_file = 'NONE')]" mode="pages">
	<xsl:variable name="pagebreak_id" select="$pagebreak_ids/page[@document_id=generate-id(current())]"/>
	
	<!-- pagebreak.xml: regular page break precedes a node -->
	<!-- if the ID of the current node matches one based on pagebreaks, a pagebreak anchor should be positioned here; $pagebreak_electronic will probably have excluded e-only pages -->
	<xsl:if test="$pagebreak_id">
		<!-- page marker -->
		<xsl:text disable-output-escaping="yes">&lt;/page&gt;</xsl:text>
		<anchor>
			<xsl:attribute name="id" select="concat('p', $pagebreak_id/@name)"/>
		</anchor>
		<xsl:text disable-output-escaping="yes">&lt;page&gt;</xsl:text>
	
	</xsl:if>

	<!-- element's text is not needed in wordcount -->

</xsl:template>


<!-- retain only text nodes of other nodes -->
<xsl:template match="*" mode="pages">
	<xsl:variable name="pagebreak_id" select="$pagebreak_ids/page[@document_id=generate-id(current())]"/>


	<!-- pagebreak.xml: regular page break precedes a node -->
	<!-- if the ID of the current node matches one based on pagebreaks, a pagebreak anchor should be positioned here; $pagebreak_electronic will probably have excluded e-only pages -->
	<xsl:if test="$pagebreak_id">
		<!-- page marker -->
		<xsl:text disable-output-escaping="yes">&lt;/page&gt;</xsl:text>
		<anchor>
			<xsl:attribute name="id" select="concat('p', $pagebreak_id/@name)"/>
		</anchor>
		<xsl:text disable-output-escaping="yes">&lt;page&gt;</xsl:text>
	
	</xsl:if>

	<!-- element's text -->
	<xsl:text> </xsl:text><xsl:apply-templates mode="#current"/><xsl:text> </xsl:text>

</xsl:template>

<!-- text nodes -->
<xsl:template match="text()" mode="pages">
	<xsl:variable name="pagebreak_id" select="$pagebreak_ids/page[@document_id=generate-id(current())]"/>
	
	<xsl:choose>
		<!-- pagebreak.xml: @offset is located inside a text node, splitting it -->
		<!-- if the ID of the current node matches one based on pagebreaks, a pagebreak anchor should be positioned here; $pagebreak_electronic will probably have excluded e-only pages -->
		<xsl:when test="$pagebreak_id">
		
			<!-- text before offset -->
			<xsl:value-of select="substring(.,1,$pagebreak_id/@offset)"/>
			
			<!-- page marker -->
			<xsl:text disable-output-escaping="yes">&lt;/page&gt;</xsl:text>
			<anchor>
				<xsl:attribute name="id" select="concat('p', $pagebreak_id/@name)"/>
			</anchor>
			<xsl:text disable-output-escaping="yes">&lt;page&gt;</xsl:text>
			
			<!-- text after offset -->
			<xsl:value-of select="substring(.,$pagebreak_id/@offset)"/>
		
		</xsl:when>
		
		<!-- not located at a pagebreak marker, complete text is required -->
		<xsl:otherwise>
			<xsl:value-of select="normalize-space()"/>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>


<!-- This is where the counts would be produced if I could get the XML above into $pages as a nodeset instead of a d-o-e string -->
<xsl:template match="/">
	<xsl:apply-templates mode="pages"/>
<!--<xsl:value-of disable-output-escaping="yes" select="$pages"/>
	<xsl:apply-templates select="$pages" mode="counter"/>-->
</xsl:template>

<xsl:template match="root" mode="counter">
	<copy><xsl:copy-of select="$pages"/><xsl:apply-templates mode="#current"/></copy>
</xsl:template>

<xsl:template match="page" mode="counter">
	<page number="following-sibling::anchor[1]/@id"><xsl:value-of select="string-length(normalize-space(.))"/></page>
</xsl:template>


</xsl:stylesheet>