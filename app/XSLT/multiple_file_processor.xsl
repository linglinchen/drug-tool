<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<!-- http://stackoverflow.com/questions/6824059/how-to-transform-and-merge-multiple-xml-files-into-a-single-one -->

<xsl:include href="identity.xsl"/>

<xsl:template match="/*">
    <products>
        <xsl:for-each select="file">
            <xsl:apply-templates 
                select="document(.)/*//product">
                <xsl:with-param name="file" select="."/>
            </xsl:apply-templates>
        </xsl:for-each>
    </products>
</xsl:template>

<xsl:template match="product">
    <xsl:param name="file"/>
    <xsl:copy>

        <xsl:apply-templates select="@*"/>

        <xsl:if test="not(id)">
            <id><xsl:value-of select="@id"/></id>
        </xsl:if>

        <xsl:apply-templates select="node()"/>

        <shopid><xsl:value-of select="$file"/></shopid>

    </xsl:copy>
</xsl:template>

<xsl:template match="category/@id|product/@id"/>

</xsl:stylesheet>