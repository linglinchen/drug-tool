<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fo="http://www.w3.org/1999/XSL/Format" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:fn="http://www.w3.org/2005/xpath-functions"
				xmlns:bk="http://www.elsevier.com/xml/bk/dtd"
				xmlns:html="http://www.w3.org/1999/xhtml"
				xmlns:aid="http://ns.adobe.com/AdobeInDesign/4.0/"
				xmlns:sb="http://www.elsevier.com/xml/common/struct-bib/dtd"
				xmlns:ce="http://www.elsevier.com/xml/common/dtd"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				xmlns:mml="http://www.w3.org/Math/DTD/mathml2/mathml2.dtd"
				exclude-result-prefixes="bk html aid sb ce xlink mml">
	<!-- Book.dtd word/page and character/page count STEP 2: Create count
	2016-09-26 JWS	original

	Run this second in XMLSpy after creating the countable nodes with Step 1. It creates an output that may be copy and pasted into Excel to count pages...characters/page...words/page (1st line is SUM)
	 -->


<xsl:output method="html" encoding="utf-8" indent="no"
 omit-xml-declaration="yes"
 media-type="text/html"/>

<xsl:param name="devmode" select="'N'"/> <!-- 'Y' will output with tags in place -->


<xsl:variable name="newline">
		<xsl:text>
</xsl:text>
</xsl:variable>


<!-- This is where the counts will be produced -->
<xsl:template match="/">
	<xsl:apply-templates mode="counter"/>
</xsl:template>

<xsl:template match="root[$devmode='Y']" mode="counter">
	<copy><xsl:apply-templates mode="#current"/></copy>
</xsl:template>

<xsl:template match="root[$devmode='N']" mode="counter">
	<xsl:text>=A</xsl:text><xsl:value-of select="count(//page)"/><xsl:text>-A2 + 1	=SUM(B2:B</xsl:text><xsl:value-of select="count(//page)"/><xsl:text>)/A1	=SUM(C2:C</xsl:text><xsl:value-of select="count(//page)"/><xsl:text>)/A1</xsl:text><xsl:value-of select="$newline"/>
	<xsl:apply-templates mode="#current"/>
</xsl:template>

<xsl:template match="page[$devmode='Y']" mode="counter">
	<page number="{substring-after(following-sibling::anchor[1]/@id, 'p')}"><xsl:value-of select="string-length(normalize-space(.))"/><xsl:text>	</xsl:text><xsl:value-of select="string-length(.) - string-length(normalize-space(.))"/></page>
</xsl:template>

<xsl:template match="page[$devmode='N']" mode="counter">
	<xsl:value-of select="substring-after(following-sibling::anchor[1]/@id, 'p')"/><xsl:text>	</xsl:text><xsl:value-of select="string-length(normalize-space(.))"/><xsl:text>	</xsl:text><xsl:value-of select="concat(string-length(normalize-space(.)) - string-length(translate(normalize-space(.), ' ', '')), $newline)"/>
</xsl:template>

<xsl:template match="page[not(following-sibling::anchor)]" mode="counter"/>

<xsl:template match="anchor" mode="counter"/>


</xsl:stylesheet>