<?xml version="1.0"?>
<xsl:stylesheet xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output encoding="utf-8" indent="yes" method="html" version="1.0"/>
	<xsl:template match="/">
		<html>
			<head>
				<!-- colour names from https://www.w3schools.com/colors/colors_names.asp -->
				<style>
					li {
						padding:0.4em;
					}
					
					/* a */
					
					.author {
						background: Gold;
					}
										
					/* b */
					
					/* c */
					
					.citation-number {
						background: LightGray;
					}	
					
					.collection-title {
						background: MediumSpringGreen;
					}
									
					
					.container-title {
						background: MediumSpringGreen;
					}
					
					/* d */
					
					.date {
						background: Tomato;
					}

					.director {
						background: Tomato;
					}
					
					.doi {
						background: RoyalBlue;
					}
					
					/* e */
					
					.edition {
						background: GoldenRod;
					}					
					
					.editor {
						background: GoldenRod;
					}
					
					/* genre */
					.genre {
						background: LightGray;
					}
					
					/* i */
					
					.isbn {
						background: RoyalBlue;
					}
					
					/* j */
					
					.journal {
						background: LightSkyBlue;
					}
					
					/* l */
					
					.location {
						background: Wheat;
					}
					
					/* m */
					
					.medium {
						background: Beige;
					}
										
					/* n */
					
					.note {
						background: Beige;
					}					
					
					/* p */
					
					.pages {
						background: Magenta;
					}
					
					.producer {
						background: Beige;
					}					
					
					.publisher {
						background: Yellow;
					}
					
					/* s */
					.source {
						background: LightGray;
					}
					
					/* t */
					.title {
						background: LimeGreen;
					}
					
					.translator {
						background: Beige;
					}										
					
					/* u */
					.url {
						background: Silver;
					}
					
					
					/* v */
					.volume {
						background: Tan;
					}

					
					
					
					
					
				</style>
			</head>
			<body>
		
		
			<ul>
				<xsl:apply-templates select="//sequence"/>
			</ul>
			
			</body>
		</html>
	</xsl:template>
	
	<xsl:template match="sequence">
	<li>
		<!-- list all children -->
		<xsl:for-each select="node()">
			<!-- ignore anything that isn't a node, such as a comment -->
			<xsl:if test="text()">
				<span>
					<xsl:attribute name="class">
						<xsl:value-of select ="local-name()"/>
					</xsl:attribute>			
				 <xsl:value-of select="."/>
				</span>
				<xsl:text> </xsl:text>
            </xsl:if>
        </xsl:for-each>
	</li>
	</xsl:template>
	
	
	
</xsl:stylesheet>
