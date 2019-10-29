<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//ES//DTD procedures DTD version 1.0//EN//XML" "c:\temp\delete\Consult\Procedures\procedure_0_1.dtd">

<xsl:stylesheet version="1.0"
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
		
		xmlns:xhtml="http://www.w3.org/1999/xhtml"

		xmlns="http://www.elsevier.com/xml/schema/transport/tfs-1.0/tfs"
		xmlns:tfs="http://www.elsevier.com/xml/schema/transport/tfs-1.0/tfs"
		
		
		exclude-result-prefixes="xhtml dct ecm ecroles eck xs rdf rdfs prism pav xlink tfs">

<!--
	produces list of images and caption files (multimedia) to fetch from S3; note the several params
-->

<!-- suppress xml declaration for text/array output and use escaped text when XML is output -->
<xsl:output method="xml" encoding="utf-8" indent="yes"
 omit-xml-declaration="yes"
 media-type="text/html"/>
 

 
<xsl:param name="output_format" select="'text'"/>	<!-- text=list of filepaths only; fullpath=list of files as URLs if provided; array_php=PHP-consumable array of filepaths; dataset=Elsevier's XML dataset format -->
<xsl:param name="imageserver" select="false()"/>	<!-- base path where exportable files are located -->
<xsl:param name="legacy" select="false()"/>	<!-- directory of legacy images -->
<xsl:param name="suggested" select="false()"/>	<!-- TODO: directory of suggested images -->
<xsl:param name="exportstart" select="110000"/>	<!-- arbitrary starting point for this export's dataset id -->
<xsl:param name="exportsequence" select="0"/>	<!-- sequence of atom within current export -->

<xsl:variable name="basehref" select="translate((/xhtml:html/xhtml:head/xhtml:base/@href | /html/head/base/@href), ':', '_')"/>

<xsl:variable name="newline">
	<xsl:text>&#xa;</xsl:text>
</xsl:variable>



<xsl:template match="xhtml:html | html">

	<xsl:choose>
		<xsl:when test="$output_format = 'text'">
			<xsl:apply-templates select="descendant::ecm:hasRelationship/ecm:Relationship[dct:title[.='Described Multimedia Resource']]/ecm:targetResource | descendant::xhtml:img | descendant::img" mode="text"/>
		</xsl:when>
		<xsl:when test="$output_format = 'fullpath'">
			<xsl:apply-templates select="descendant::ecm:hasRelationship/ecm:Relationship[dct:title[.='Described Multimedia Resource']]/ecm:targetResource | descendant::xhtml:img | descendant::img" mode="fullpath"/>
		</xsl:when>
		<xsl:when test="$output_format = 'array_php'">
			<xsl:text>var $files_to_fetch = new Array();</xsl:text><xsl:value-of select="$newline"/>
			
			<xsl:apply-templates select="descendant::ecm:hasRelationship/ecm:Relationship[dct:title[.='Described Multimedia Resource']]/ecm:targetResource | descendant::xhtml:img | descendant::img" mode="array_php"/>
		</xsl:when>
		<xsl:when test="$output_format = 'dataset'">
			<xsl:apply-templates select="." mode="dataset"/>
		</xsl:when>
		
		<xsl:otherwise>
			<xsl:text>ERROR: unrecognized $output_format: </xsl:text>
			<xsl:value-of select="$output_format"/>
		</xsl:otherwise>
	</xsl:choose>

</xsl:template>


<!-- TEXT OUTPUT, useful for testing -->
<xsl:template match="ecm:targetResource | dct:title" mode="text">
	<xsl:call-template name="video_captions">
		<xsl:with-param name="output" select="'closedcaptions'"/>
	</xsl:call-template>
	<xsl:value-of select="$newline"/>

	<xsl:call-template name="video_captions">
		<xsl:with-param name="output" select="'transcript'"/>
	</xsl:call-template>
	<xsl:value-of select="$newline"/>
</xsl:template>

<xsl:template match="xhtml:img | img" mode="text">
	<xsl:value-of select="@src"/><xsl:value-of select="$newline"/>
</xsl:template>



<!-- FULLPATH OUTPUT, useful for export when the file format is identified in XML -->
<xsl:template match="ecm:targetResource | dct:title" mode="fullpath">
	<xsl:call-template name="basepath"/>
	<xsl:call-template name="video_captions">
		<xsl:with-param name="output" select="'closedcaptions'"/>
	</xsl:call-template>
	<xsl:value-of select="$newline"/>

	<xsl:call-template name="basepath"/>
	<xsl:call-template name="video_captions">
		<xsl:with-param name="output" select="'transcript'"/>
	</xsl:call-template>
	<xsl:value-of select="$newline"/>
</xsl:template>

<xsl:template match="xhtml:img | img" mode="fullpath">
	<xsl:call-template name="basepath"/><xsl:value-of select="@src"/><xsl:value-of select="$newline"/>
</xsl:template>



<!-- PHP ARRAY, useful for fetching remote files during export -->
<xsl:template match="ecm:targetResource | dct:title" mode="array_php">
	<xsl:text>$files_to_fetch[] = '</xsl:text>
	<xsl:call-template name="basepath"/>
	<xsl:call-template name="video_captions">
		<xsl:with-param name="output" select="'closedcaptions'"/>
	</xsl:call-template>
	<xsl:text>';</xsl:text><xsl:value-of select="$newline"/>
	
	<xsl:text>$files_to_fetch[] = '</xsl:text>
	<xsl:call-template name="basepath"/>
	<xsl:call-template name="video_captions">
		<xsl:with-param name="output" select="'transcript'"/>
	</xsl:call-template>
	<xsl:text>';</xsl:text><xsl:value-of select="$newline"/>
</xsl:template>

<xsl:template match="xhtml:img | img" mode="array_php">
	<xsl:text>$files_to_fetch[] = '</xsl:text>
	<xsl:call-template name="basepath"/>
	<xsl:value-of select="@src"/>
	<xsl:text>';</xsl:text><xsl:value-of select="$newline"/>
</xsl:template>



<!-- DATASET.XML contained within export package file; outputs XML declaration -->
<xsl:template match="xhtml:html | html" mode="dataset">
<xsl:text disable-output-escaping="yes">&lt;?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot;?&gt;</xsl:text><xsl:value-of select="$newline"/>
<dataset
	xmlns="http://www.elsevier.com/xml/schema/transport/tfs-1.0/tfs"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.elsevier.com/xml/schema/transport/tfs-1.0/tfs
	http://www.elsevier.com/xml/schema/transport/tfs-1.0/tfs.xsd"
	schema-version="1.0">
	
	<!-- identifiers for the exported package being sent -->
	<dataset-unique-ids>
		<xsl:apply-templates select="xhtml:head/rdf:RDF/rdf:Description/pav:createdBy | head/rdf:RDF/rdf:Description/pav:createdBy"/>
		<supplier-dataset-id>
			<xsl:value-of select="$exportstart + $exportsequence"/>
		</supplier-dataset-id>
		<xsl:apply-templates select="xhtml:head/rdf:RDF/rdf:Description/ecm:version | head/rdf:RDF/rdf:Description/ecm:version"/>
	</dataset-unique-ids>
	
	<!-- instruction to receiver on action to take with exported package; we're always doing ADD because that's all we know CK does -->
	<dataset-properties>
		<dataset-action>ADD</dataset-action>
	</dataset-properties>
	
	<!-- list of external data files included in exported package -->
	<dataset-content>
		<item>
			<xsl:apply-templates select="xhtml:head/xhtml:base | head/base"/>
			<xsl:apply-templates select="./@xsi:schemaLocation"/>
		
			<xsl:apply-templates select="descendant::ecm:hasRelationship/ecm:Relationship[dct:title[.='Described Multimedia Resource']]/ecm:targetResource | descendant::xhtml:img | descendant::img" mode="dataset"/>
		</item>
	</dataset-content>

</dataset>

</xsl:template>


<xsl:template match="ecm:version">
	<timestamp>
		<xsl:value-of select="substring-after(., /xhtml:html/xhtml:head/xhtml:base/@href)"/>

		<!-- arbitrarily sets seconds to 00 if not already in timestamp -->
		<xsl:if test="translate(., translate(., ':', ''), '') = '::'">
			<xsl:text>:00</xsl:text>
		</xsl:if>
	</timestamp>
</xsl:template>

<xsl:template match="pav:createdBy">
	<supplier-code>
		<xsl:apply-templates/>
	</supplier-code>
</xsl:template>

<xsl:template match="@xsi:schemaLocation">
	<xsl:variable name="schema" select="substring-after(., ' ')"/>	<!-- get second schema, after http://www.w3.org/1999/xhtml -->
	<xsl:variable name="version" select="number(substring-after(substring-before($schema, '.xsd'), 'XHTML_')) div 10"/>
	
	<version-number>
		<xsl:value-of select="$version"/>
	</version-number>
	<schema-version>
		<xsl:if test="starts-with($schema, 'ELS_XHTML_')">
			<xsl:text>Elsevier XHTML </xsl:text>
		</xsl:if>
		<xsl:value-of select="$version"/>
	</schema-version>

</xsl:template>

<xsl:template match="/xhtml:html/xhtml:head/xhtml:base | /html/head/base">
	<pathname>
		<xsl:value-of select="$basehref"/>
		<xsl:text>/</xsl:text>
		<xsl:value-of select="$basehref"/>
		<xsl:text>.html</xsl:text>
	</pathname>
	<md5>__REPLACE_ME__</md5>
	<item-id>
		<xsl:value-of select="$basehref"/>
	</item-id>
</xsl:template>

<xsl:template match="ecm:targetResource | dct:title" mode="dataset">
	<asset>
		<pathname>
			<xsl:value-of select="$basehref"/>
			<xsl:text>/</xsl:text>
			<xsl:call-template name="video_captions">
				<xsl:with-param name="output" select="'closedcaptions'"/>
			</xsl:call-template>		
		</pathname>
		<md5>__REPLACE_ME__</md5>
	</asset>
	
	<asset>
		<pathname>
			<xsl:value-of select="$basehref"/>
			<xsl:text>/</xsl:text>
			<xsl:call-template name="video_captions">
				<xsl:with-param name="output" select="'transcript'"/>
			</xsl:call-template>
		</pathname>
		<md5>__REPLACE_ME__</md5>
	</asset>
</xsl:template>

<xsl:template match="xhtml:img | img" mode="dataset">
	<asset>
		<pathname><xsl:value-of select="$basehref"/><xsl:text>/</xsl:text><xsl:value-of select="@src"/></pathname>
		<md5>__REPLACE_ME__</md5>
	</asset>
</xsl:template>





<xsl:template name="video_captions">
	<xsl:param name="output"/>
	<xsl:value-of select="substring-before(substring-after(@rdf:resource, '/'), '-V-')"/>
	
	<xsl:choose>
		<xsl:when test="$output = 'closedcaptions'">
			<xsl:text>-Y-CC.xml</xsl:text>
		</xsl:when>
		<xsl:when test="$output = 'transcript'">
			<xsl:text>-Y-CT.xml</xsl:text>
		</xsl:when>
	</xsl:choose>
</xsl:template>


<!-- expands provided params into URL paths -->
<xsl:template name="basepath">
	<xsl:if test="$imageserver">
		<xsl:value-of select="$imageserver"/>
	</xsl:if>
	<xsl:if test="$legacy">
		<xsl:value-of select="$legacy"/>
	</xsl:if>

</xsl:template>


</xsl:stylesheet>
