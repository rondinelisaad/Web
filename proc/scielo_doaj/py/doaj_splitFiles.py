#!/usr/bin/env python
# coding:utf-8
import os
from lxml import etree
from doaj_config import *
import codecs

size = 0

def escrever(saida, content):
    global size
    saida.write(content)
    size+=len(content)

def validateXML(schema_file,xml_file,log_file):
    schema_file = open(schema_file,'r')
    xml_file = open(xml_file,'r')
    schema = schema_file
    xmlschema_doc = etree.parse(schema)
    xmlschema = etree.XMLSchema(xmlschema_doc)
    xml = xml_file
    doc = etree.parse(xml)
    log_file.write(str(xmlschema.validate(doc)))
    schema_file.close()
    xml_file.close()



file_count=1
line_count=0
file_path=proc_path+'/scielo_doaj/output/doaj/file_%02d.xml'
file_log_path=proc_path+'/scielo_doaj/output/doaj/file_log_%02d.txt'
file_schema_path=proc_path+'/scielo_doaj/xsd/doajArticles.xsd'

#mxStringb = '''iconv -f ISO-8859-1 -t UTF-8 %s/scielo_doaj/output/doaj/file.xml -o %s/scielo_doaj/output/doaj/file.utf.xml'''%(proc_path,proc_path)
#os.popen(mxStringb)

for myline in codecs.open(proc_path+'/scielo_doaj/output/doaj/file.xml'):
    line_count+= 1
    if line_count == 1:
        saida = codecs.open(file_path%file_count,'w','utf-8')
        log   = codecs.open(file_log_path%file_count,'w','utf-8')
        escrever(saida,'<?xml version="1.0" encoding="utf-8" ?>')
        escrever(saida,'<records>')
    
    escrever(saida,myline.decode('iso-8859-1'))
    print "creating file %s with %s bytes"%(file_count,size)

    if size > max_file_size:
       if '</record>' in myline:
            escrever(saida,'</records>')
            saida.close()
            validateXML(file_schema_path,file_path%file_count,log)
            log.close()
            file_count+=1
            line_count=0
            size=0
