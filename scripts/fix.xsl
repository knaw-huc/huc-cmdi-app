<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:xs="http://www.w3.org/2001/XMLSchema"
    xmlns:cmd="http://www.clarin.eu/cmd/" 
    exclude-result-prefixes="xs"
    version="2.0">
    
    <xsl:template match="node() | @*">
        <xsl:copy>
            <xsl:apply-templates select="node() | @*"/>
        </xsl:copy>
    </xsl:template>

    <xsl:variable name="rp" select="/cmd:CMD/cmd:Resources/cmd:ResourceProxyList/cmd:ResourceProxy[@id='p1']/cmd:ResourceRef"/>
    <xsl:variable name="p" select="replace($rp,'.*/([^.]*).jpg','$1')"/>
    <xsl:variable name="v" select="replace($p,'([^_]*)_.*','$1')"/>
    <xsl:variable name="s" select="replace($p,'([^_]*_.*)-.*','$1')"/>

    <xsl:template match="cmd:Vragenlijst/cmd:identifier">
        <xsl:copy>
            <xsl:value-of select="$v"/>
        </xsl:copy>
    </xsl:template>
    
    <xsl:template match="cmd:Scan/cmd:id">
        <xsl:copy>
            <xsl:value-of select="$s"/>
        </xsl:copy>
    </xsl:template>
    
    <xsl:template match="comment()" priority="1"/>
    
    <xsl:template match="cmd:Pagina">
        <xsl:copy>
            <xsl:apply-templates select="@*"/>
            <xsl:if test="empty(cmd:id)">
                <xsl:variable name="rp" select="@ref"/>
                <cmd:id>
                    <xsl:value-of select="replace(/cmd:CMD/cmd:Resources/cmd:ResourceProxyList/cmd:ResourceProxy[@id=$rp]/cmd:ResourceRef,'.*/([^.]*).*','$1')"/>
                </cmd:id>
            </xsl:if>
            <xsl:apply-templates select="node()"/>
        </xsl:copy>
    </xsl:template>
    
    <xsl:template match="cmd:Resources">
        <xsl:copy>
            <xsl:apply-templates select="node() | @*"/>
            <xsl:if test="empty(cmd:JournalFileProxyList)">
                <cmd:JournalFileProxyList/>
            </xsl:if>            
            <xsl:if test="empty(ResourceRelationList)">
                <cmd:ResourceRelationList/>
            </xsl:if>
        </xsl:copy>        
    </xsl:template>
</xsl:stylesheet>