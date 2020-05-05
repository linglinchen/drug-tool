<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="3.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:fn="http://www.w3.org/2005/xpath-functions">
	<xsl:output method="xml" version="1.0" encoding="UTF-8" indent="yes"/>
	
<!--
Counts the number of uses of a unique value of category in a Dictionary
2018-02-08 JWS	original


	0. be sure your XSL transformation tool supports XSLT 2
	1. replace the source XML's DOCTYPE with the DOCTYPE from this XSLT. This removes the unfollowable reference to the old DTD and adds support for known entities
	2. transform the source XML against this XSLT
	3. output should have a row for each distinct category ID, plus the number of uses of that category in the dictionary

-->
	
<xsl:template match="dictionary">
	<!-- create a variable of the unique values -->
	<xsl:variable name="categories_unique">
		<xsl:for-each select="distinct-values(//category)">
			<xsl:element name="category"><xsl:value-of select="."/></xsl:element>
		</xsl:for-each>
	
	</xsl:variable>
	
	<!-- create a document fragment of all the category assignments in the dictionary -->
	<xsl:variable name="categories_all">
		<xsl:copy-of select="//category"/>
	</xsl:variable>
	

		<xsl:text>
</xsl:text>

	<!-- loop through the unique categories and generate output -->
	<xsl:for-each select="$categories_unique/category">
		<xsl:variable name="category"><xsl:value-of select="."/></xsl:variable>
		
		<xsl:value-of select="concat($category, ': ')"/><xsl:value-of select="count($categories_all/category[.=$category])"/>
		<xsl:text>
</xsl:text>	
	
	</xsl:for-each>


</xsl:template>	

	
</xsl:stylesheet>
