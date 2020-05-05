<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:bk="http://www.elsevier.com/xml/bk/dtd"
				xmlns:html="http://www.w3.org/1999/xhtml"
				xmlns:aid="http://ns.adobe.com/AdobeInDesign/4.0/"
				xmlns:sb="http://www.elsevier.com/xml/common/struct-bib/dtd"
				xmlns:ce="http://www.elsevier.com/xml/common/dtd"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				xmlns:mml="http://www.w3.org/Math/DTD/mathml2/mathml2.dtd"
				exclude-result-prefixes="bk html aid sb ce xlink mml"><!-- remove mml and add default namespace (bk) when output includes MathML -->
	<!--
		Overrides ebook_booksdtd_drug.xsl built originally for Skidmore-Roth NDR 2017
		2017-05-30 JWS	original
		2017-08-15 JWS	$confusion_prefix; $confusion_style; $confusion_location; disabled Pregnancy Category; fixed-combination; noncrush; icon08=intravenous; name=drugname; toprx; reduce section depth; Nurse Alert block omitted and block is now marked as alert; DTD 3.6
		2017-10-13 JWS	TODO: look at ebook_booksdtd_drug-hodgson-fixitem.xsl for a way forward on fixing bulleted list transformation dropping child nodes and following-sibling text
	-->
	<!-- TODO: normalize-space() everywhere -->

<!-- imports master Books.DTD IC processor XSLT -->
<xsl:import href="ebook_booksdtd_drug.xsl"/>

<xsl:output method="xml" encoding="utf-8" indent="yes"
 omit-xml-declaration="yes"
 doctype-public="-//ES//DTD drug_guide DTD version 3.2//EN//XML" doctype-system="Y:\WWW1\METIS\Drugs\3_6_drug.dtd"
 media-type="text/html"/>

<xsl:strip-space elements="ce:section ce:section-title ce:para ce:list ce:list-item"/>
<xsl:preserve-space elements="br"/>

  	<!-- here just in case they need to be; delete if not used -->
  <xsl:param name="thispage" select="'NONE'"/>	<!-- optional; indicates the requesting page path; requires usage_type to be able to look for a specific item -->
  <xsl:param name="asset_location" select="'book/'"/>
  <xsl:param name="searchedpage" select="'NONE'"/>	<!-- optional; path of page being searched -->
  <xsl:param name="browser"/>
  <xsl:param name="sectionID" select="'NONE'"/>	<!-- optional; requests a specific ID on the XML document to look for -->
  <xsl:param name="devmode" select="'N'"/>	<!-- "brokenimage" will hide FigureCaption in production when images are missing; "Y" replaces broken images with error message and outputs other things; "full" displays the original XML at the end of the monograph -->
  <xsl:param name="imagenaming" select="'simple'"/>	<!-- "simple" will transform images from on133-040-9780323172929.jpg to 133040.jpg -->
  <xsl:param name="preg_term" select="'HODGSON_DOES_NOT_USE_Pregnancy ('"/>
  <xsl:param name="confusion_prefix" select="'Do not confuse '"/>
  <xsl:param name="confusion_style" select="'sentence'"/>
  <xsl:param name="confusion_location" select="'info'"/>



<xsl:template name="monograph_attr">
		<xsl:attribute name="id"><xsl:value-of select="concat('m', '_REPLACE_ME__')"/></xsl:attribute><!--count(preceding-sibling::ce:section) + 1-->
		<xsl:attribute name="status">active</xsl:attribute>
		<xsl:if test="descendant::ce:link/@locator='icon07-top100-9780323525091' or descendant::ce:link/@locator='icon07-top100-9780323525091'">
			<xsl:attribute name="top">100</xsl:attribute>
		</xsl:if>
		<xsl:if test="descendant::ce:link/@locator='icon07-9780323448260' or descendant::ce:link/@locator='icon08-9780323448260'">
			<xsl:attribute name="ru">yes</xsl:attribute>
		</xsl:if>
		<xsl:if test="descendant::ce:link/@locator='icon05-highalert-9780323525091' or descendant::ce:link/@locator='icon05-highalert-9780323525091'">
			<xsl:attribute name="ha">yes</xsl:attribute>
		</xsl:if>
		<xsl:apply-templates select="ce:section-title" mode="mono_name"/>
</xsl:template>

<!-- CANadian drug -->
<xsl:template match="ce:inline-figure[ce:link/@locator = 'icon02-canadian-9780323525091']" mode="#all"/>
<!-- "rarely used" is a monograph attribute -->
<xsl:template match="ce:inline-figure[ce:link/@locator = 'icon07-9780323448260']" mode="mono_name"/>
<!-- "high alert" is a monograph attribute -->
<xsl:template match="ce:inline-figure[ce:link/@locator = 'icon05-highalert-9780323525091']" mode="mono_name"/>
<!-- "top=100" is a monograph attribute -->
<xsl:template match="ce:inline-figure[ce:link/@locator = 'icon07-top100-9780323525091']" mode="mono_name"/>
<!-- IV meds used as qualifier on sec_title -->
<xsl:template match="ce:inline-figure[ce:link/@locator = 'icon08-iv-9780323525091']" mode="#all"/>
<xsl:template match="ce:section[ce:section-title/ce:inline-figure/ce:link[@locator='icon08-iv-9780323525091']]" mode="subsection">
	<section type="intravenous">
		<xsl:attribute name="id">
			<xsl:value-of select="concat('s', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<xsl:apply-templates mode="#current"/>
	</section>
</xsl:template>

<!-- IV meds, results in section/@type=intravenous -->
<xsl:template match="ce:inline-figure[ce:link/@locator = 'icon09-iv-9780323525091']" mode="#all"/>
<!-- Only results in a marker -->
<xsl:template match="ce:inline-figure[ce:link/@locator = 'icon11-lifespan-9780323525091']" mode="#all"/>

<!-- non-crush marker that also renders a rule above; usually starts a new para, but sometimes splits it -->
<xsl:template match="ce:inline-figure[ce:link/@locator = 'icon10-noncrush-9780323525091']" mode="#all"/>

<!-- results in marker and overline separator -->
<xsl:template match="ce:para[ce:inline-figure[ce:link/@locator = 'icon10-noncrush-9780323525091']]" name="noncrush_after" mode="#all">
	<para type="noncrush">
		<xsl:attribute name="id">
			<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		
		<xsl:apply-templates select="node()[preceding-sibling::ce:inline-figure[ce:link/@locator = 'icon10-noncrush-9780323525091']]" mode="#current"/>
	</para>
</xsl:template>

<!-- sometimes crushables are mixed with non-crushables, this separates them -->
<xsl:template match="ce:para[ce:inline-figure[ce:link/@locator = 'icon10-noncrush-9780323525091']/preceding-sibling::node()]" mode="#all">
	<para>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<xsl:apply-templates select="node()[following-sibling::ce:inline-figure[ce:link/@locator = 'icon10-noncrush-9780323525091']]" mode="#current"/>
	</para>
	<xsl:call-template name="noncrush_after"/>
</xsl:template>

<!-- BEGIN fractions; ordered in increasing size -->
<xsl:template match="ce:inline-figure[ce:link[@locator='icon15-9780323525091']]" mode="#all">
	<math><mfrac bevelled="true"><mi>1</mi><mi>10</mi></mfrac></math>
</xsl:template>
<!-- END fractions -->


<!-- the asterisk indicates the monograph's name is a tallman drug, which will be accomplished with <tallman> -->
<xsl:template match="text()[parent::ce:section-title and starts-with(., '*')]" mode="mono_name">
	<xsl:value-of select="replace(., '^\*', '')"/>
</xsl:template>


<!-- BEGIN schedule rules -->
<xsl:template match="ce:bold[contains(., 'Schedule')]" mode="schedule">
	<schedule>
		<xsl:attribute name="num">
			<xsl:for-each select="tokenize(.//text(), '\s')"><xsl:call-template name="schedule_num"/></xsl:for-each>
		</xsl:attribute>
		<xsl:value-of select="replace(., ' CLINICAL:?', '')"/>
	</schedule>
	<xsl:if test="preceding-sibling::ce:bold[ends-with(., 'CLINICAL:')]">
		<xsl:value-of select="following-sibling::text()[1]"/>
	</xsl:if>
</xsl:template>

<xsl:template name="schedule_num">
	<xsl:variable name="num" select="translate(., ',;:.)', '')"/>
	<xsl:if test="$num='I' or $num='II' or $num='III' or $num='IV' or $num='V' or number($num) = number($num)">
		<xsl:choose>
			<xsl:when test="$num='I'">1</xsl:when>
			<xsl:when test="$num='II'">2</xsl:when>
			<xsl:when test="$num='III'">3</xsl:when>
			<xsl:when test="$num='IV'">4</xsl:when>
			<xsl:when test="$num='V'">5</xsl:when>
			<xsl:when test="number($num) = number($num)"><xsl:value-of select="."/></xsl:when>
		</xsl:choose>
	</xsl:if>
</xsl:template>
<!-- END schedule rules -->


<!-- Do Not Confuse paragraph -->
<!-- keeps entirety of text in single element -->
<xsl:template match="ce:para[$confusion_location = 'info']" mode="confusion">
	<xsl:variable name="confusion_node"><xsl:value-of select="ce:bold"/></xsl:variable>
	<xsl:variable name="parent_node"><xsl:copy-of select="."/></xsl:variable>
	<confusion>
		<xsl:apply-templates mode="#current"/>
	</confusion>
</xsl:template>


<!-- select only that string after the inline head and before the next inline head -->
<xsl:template match="ce:para" mode="pharma">
	<class type="pharma">
		<xsl:apply-templates select="ce:bold[.='PHARMACOTHERAPEUTIC:']/following-sibling::node()[not(local-name()='bold') and preceding-sibling::ce:bold[1][.='PHARMACOTHERAPEUTIC:']]" mode="#current"/>
		<xsl:apply-templates select="ce:bold[.='PHARMACOTHERAPEUTIC:']/following-sibling::node()[local-name()='bold' and contains(., 'Schedule') and preceding-sibling::ce:bold[1][.='PHARMACOTHERAPEUTIC:']]" mode="schedule"/>
	</class>
</xsl:template>
<xsl:template match="ce:para" mode="clinical">
	<class type="clinical">
		<xsl:apply-templates select="ce:bold[ends-with(., 'CLINICAL:')]/following-sibling::node()[not(local-name()='bold') and preceding-sibling::ce:bold[1][ends-with(., 'CLINICAL:')]]" mode="#current"/>
		<xsl:apply-templates select="ce:bold[ends-with(., 'CLINICAL:')]/following-sibling::node()[local-name()='bold' and contains(., 'Schedule') and preceding-sibling::ce:bold[1][ends-with(., 'CLINICAL:')]]" mode="schedule"/>
	</class>
</xsl:template>


<!-- exclude these inline heads -->
<xsl:template match="ce:bold[.='PHARMACOTHERAPEUTIC:']" mode="pharma"/>
<xsl:template match="ce:bold[ends-with(., 'CLINICAL:')]" mode="clinical"/>



<!-- does not use parens -->
<xsl:template name="get_pronunciation">
	<xsl:apply-templates select="ce:para[position()=1 and not(starts-with(., '('))]" mode="pronunciation"/>
</xsl:template>

<!-- surrounded by parens -->
<xsl:template name="get_tradenames">
	<xsl:apply-templates select="ce:para[(position()=1 or position()=2) and (starts-with(., '(') and not(contains(ce:italic, 'Func. class.:')) and not(contains(ce:italic, 'Chem. class.:')) and not(starts-with(., $confusion_prefix)) and not(contains(., 'BLACK BOX ALERT')))]" mode="tradenames"/>
</xsl:template>

<!-- underline here means the drug is a top-prescribed one -->
<xsl:template name="drugname">
	<xsl:param name="name" select="."/>
	<xsl:choose>
		<xsl:when test=".//ce:underline[.=$name]">
			<emphasis alert="toprx">
				<xsl:value-of select="$name"/>
			</emphasis>
		</xsl:when>
		<xsl:otherwise>
			<xsl:value-of select="$name"/>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>

<!-- Book XML often has several contentless ce:section elements nested within the content; in Skidmore we kept these but in Kizior we're getting rid of them because we're converting fewer bolded strings to sec_title -->
<xsl:template match="ce:section[count(*) = 1 and ce:section]" mode="subsection">
	<xsl:apply-templates mode="#current"/>
</xsl:template>


<!-- Kizior uses bullet points to create inline lists, this restores them to lists -->
<xsl:template match="ce:para[contains(., '• ')]" mode="#all">
	<xsl:choose>
		<xsl:when test="node()[1][local-name()='bold']">
			<section type="none">
				<xsl:attribute name="id" select="concat('s', '_REPLACE_ME__')"/>
				<sec_title><xsl:apply-templates select="node()[1]"/></sec_title>
				<xsl:call-template name="fix_inline_list"/>
			</section>
		</xsl:when>
		<xsl:otherwise>
			<xsl:call-template name="fix_inline_list"/>
		</xsl:otherwise>
	</xsl:choose>
</xsl:template>	

<xsl:template name="fix_inline_list">
	<para>
		<xsl:attribute name="id" select="concat('p', '_REPLACE_ME__')"/>
		<list>
			<xsl:attribute name="id" select="concat('l', '_REPLACE_ME__')"/>
			<xsl:for-each select="node()">
				<xsl:choose>
					<!-- treated this as a sec_title already -->
					<xsl:when test="./position() = 1 and ./local-name()='bold'"/>
					
					<!-- a single text string with more than one bullet in it (most common markup) -->
					<xsl:when test="count(tokenize(., '• ')) &gt; 2">
						<xsl:for-each select="tokenize(., '• ')">
							<xsl:if test="string-length(normalize-space(translate(., ' ', ' '))) &gt; 0">
								<item>
									<label>•</label>
									<para>
										<xsl:attribute name="id" select="concat('p', '_REPLACE_ME__')"/>
										<xsl:value-of select="translate(., ' ', ' ')"/>
									</para>
								</item>
							</xsl:if>
						</xsl:for-each>			
					</xsl:when>
					
					<!-- contains children -->
					<xsl:when test="contains(., '• ')">
						<item>
							<label>•</label>
							<para>
								<xsl:attribute name="id" select="concat('p', '_REPLACE_ME__')"/>
								<xsl:value-of select="translate(substring-after(., '• '), ' ', ' ')"/>
								<xsl:apply-templates select="following-sibling::node()[not(contains(., '• ')) and preceding-sibling::text()[contains(., '• ')][1][.=current()]] | following-sibling::node()[contains(., '• ') and not(starts-with(., '• ')) and preceding-sibling::text()[contains(., '• ')][1][.=current()]]"/>
							</para>
						</item>					
					</xsl:when>
					
					<!-- ignore all other nodes, they are handled by selecting following siblings -->
					
				</xsl:choose>
			</xsl:for-each>
		</list>
	</para>

</xsl:template>

<!-- used with inline bulleted list to list converter to catch text() nodes that trail other child nodes and include the start of the next bulleted list -->
<xsl:template match="text()[contains(., '• ') and not(starts-with(., '• ')) and preceding-sibling::text()[contains(., '• ')]]">
	<xsl:value-of select="translate(substring-before(., '• '), ' ', ' ')"/>
</xsl:template>


<!-- BEGIN in Skidmore NDR these were "lifethreat" -->
<xsl:template match="ce:bold[not(parent::*/text())]" mode="subsection">
  <emphasis style="bold"><xsl:apply-templates/></emphasis>
</xsl:template>

<xsl:template match="ce:bold[not(parent::*/text())]" mode="subsubsection">
  <emphasis style="bold"><xsl:apply-templates/></emphasis>
</xsl:template>

<xsl:template match="ce:bold" mode="bbw">
  <emphasis style="bold"><xsl:apply-templates/></emphasis>
</xsl:template>
<!-- END in Skidmore NDR these were "lifethreat" -->

<!-- BEGIN handling of Nurse Alerts -->
<xsl:template match="ce:para[ce:bold[contains(., '◀ ALERT ▶')]]" mode="#all">
	<para>
		<xsl:attribute name="id">
			<xsl:value-of select="concat('p', '_REPLACE_ME__')"/><!--generate-id(.)-->
		</xsl:attribute>
		<emphasis alert="nurse">
			<xsl:apply-templates mode="subsection"/>
		</emphasis>
	</para>
</xsl:template>

<!-- retain any additional text, it's probably an inline header -->
<xsl:template match="ce:bold[contains(., '◀ ALERT ▶')]" mode="#all">
	<emphasis style="bold"><xsl:value-of select="normalize-space(concat(substring-before(., '◀ ALERT ▶'), substring-after(., '◀ ALERT ▶')))"/></emphasis>
</xsl:template>

<!-- this is the only text and we don't need it because the whole block is now marked as a Nurse Alert -->
<xsl:template match="ce:bold[text() ='◀ ALERT ▶']" mode="#all"/>
<!-- END handling of Nurse Alerts -->


<!-- this style is specified by design spec and shouldn't be declared by XML -->
<xsl:template match="ce:bold[ancestor::bk:thead]" mode="#all">
  <xsl:apply-templates mode="#current"/>
</xsl:template>

<!-- fixed-combination boldfaces tradenames -->
<xsl:template match="ce:bold[ancestor::ce:section[contains(ce:section-title, 'FIXED-COMBINATION')]]" mode="subsection">
  <drug type="trade" refid="_REFID__"><xsl:apply-templates/></drug>
</xsl:template>

</xsl:stylesheet>