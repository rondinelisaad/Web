<?php

ini_set("display_errors","1");
error_reporting(E_ALL);

require_once(dirname(__FILE__)."/../../users/functions.php");
require_once(dirname(__FILE__)."/../../users/langs.php");	
require_once(dirname(__FILE__)."/../../includes/phpmailer/class.phpmailer.php");
require_once(dirname(__FILE__)."/../../classes/services/ArticleServices.php");
require_once(dirname(__FILE__)."/../../../../php/include.php");

$bvsSiteIni = parse_ini_file(dirname(__FILE__)."/../../../../bvs-site-conf.php",true);

$baseDir = "";

if($bvsSiteIni['ENVIRONMENT']['LETTER_UNIT'] != null){
	$baseDir = $bvsSiteIni['ENVIRONMENT']['LETTER_UNIT'].$bvsSiteIni['ENVIRONMENT']['DATABASE_PATH'];
}else{
	$baseDir = $bvsSiteIni['ENVIRONMENT']['DATABASE_PATH'];
}

$scielodef = parse_ini_file($DirName."/../../scielo.def", true);
$site = parse_ini_file(dirname(__FILE__)."/../../../ini/" . $lang . "/bvs.ini", true);
$home = $scielodef['this']['url'];

$cgi = array_merge($_GET,$_POST);

$acao = $cgi["acao"];
$pid = $cgi["pid"];
$caller = $cgi["caller"];

//geting metadatas from PID
$articleService = new ArticleService($caller);
$articleService->setParams($pid);
$article = $articleService->getArticle();
switch($acao){
	case "send":
		$articleURL = 'http://'.$caller.'/scielo.php?script=sci_abstract&pid='.$pid.'&lng=en&nrm=iso&tlng=en';
		$link = '<a href="'.$articleURL.'">'.getTitle($article->getTitle()).'</a>';

		$msg = file_get_contents(dirname(__FILE__)."/../../html/".$lang."/send_mail_msg.html");

		$msg = str_replace("[toName]",$cgi["toName"],$msg);
		$msg = str_replace("[fromName]",$cgi["fromName"],$msg);
		$msg = str_replace("[serialName]",$article->getSerial(),$msg);
		$msg = str_replace("[articleTitleURL]",$link,$msg);
		$msg = str_replace("[articleURL]",$articleURL,$msg);
		$msg = str_replace("[commentary]",$cgi["comment"],$msg);


                $search = array ('@<script[^>]*?>.*?</script>@si', // Strip out javascript
                                                 '@<[\/\!]*?[^<>]*?>@si',          // Strip out HTML tags
                                                 '@([\r\n])[\s]+@',                // Strip out white space
                                                 '@&(quot|#34);@i',                // Replace HTML entities
                                                 '@&(amp|#38);@i',
                                                 '@&(lt|#60);@i',
                                                 '@&(gt|#62);@i',
                                                 '@&(nbsp|#160);@i',
                                                 '@&(iexcl|#161);@i',
                                                 '@&(cent|#162);@i',
                                                 '@&(pound|#163);@i',
                                                 '@&(copy|#169);@i',
                                                 '@&#(\d+);@e');                    // evaluate as php

                $replace = array ('',
                                                  '',
                                                  '\1',
                                                  '"',
                                                  '&',
                                                  '<',
                                                  '>',
                                                  ' ',
                                                  chr(161),
                                                  chr(162),
                                                  chr(163),
                                                  chr(169),
                                                  'chr(\1)');

                $msg_no_html = preg_replace($search, $replace, $msg);


		$_mail = new PHPMailer();
		$_mail->AddReplyTo($cgi["from"],$cgi["fromName"]);
		$_mail->From     = "appscielo@bireme.org";
		$_mail->FromName = "Scielo";
		$_mail->Subject  = $cgi["fromName"]." ".ARTICLE_SUGGESTION;
		$_mail->Host     = "esmeralda.bireme.br";
		$_mail->Password = "x@07sci@";
		$_mail->Username = "appscielo";
		$_mail->SMTPAuth =true;
		$_mail->Mailer   = "smtp";
		$_mail->IsHTML(true);
		$_mail->Body = $msg;
		$_mail->AltBody  = $msg_no_html;
		$_mail->AddAddress($cgi["to"], $cgi['toName']);
		$send = $_mail->Send();
		if(!$send)
			$message = array("ERROR" => $this->_mail->ErrorInfo);
		else
			$acao = "message";
			$message = ARTICLE_SUBMITED_WITH_SUCCESS;
	break;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title>
			<?= $site['title']?>
		</title>
		<? include(dirname(__FILE__)."/../../../../php/head.php"); ?>
		<script language="JavaScript" src="../../js/script.js"></script>
		<script language="JavaScript" src="../../js/validator.js"></script>
		<style>
			/* classes for validator */
			.tfvHighlight
				{font-weight: bold; color: red; display:inline;}
			.tfvNormal
				{font-weight: normal;	color: black; display: inline;}
		</style>
	</head>
	<body>
		<div class="container">	
			<div class="level2">
				<? require_once(dirname(__FILE__)."/../../html/" . $lang . "/headerInstancesServices.html"); ?>

				<div class="middle">				
					<!--div id="breadCrumb">
						<a href="/">
							home
						</a>
						&gt; <?=ENVIAR_ARTIGO?>
					</div-->
					<div class="content">
						<h3>
							<span>
								<?=ENVIAR_ARTIGO_POR_EMAIL?>
							</span>
						</h3>					
						<FORM action="" method="post" name="sendMail" onsubmit="return v.exec()">
						<?
						if ($acao == "message") {
						?>
							<center><?=$message?></center>
							<INPUT type="button" class="submit" value="<?=CLOSE?>" onclick="window.close();">
						<?
						}else{?>
							<INPUT type="hidden" name="acao" value="send">
							<INPUT type="hidden" name="pid" value="<?=$pid?>">							
							<INPUT type="hidden" name="caller" value="<?=$caller?>">														
							<TABLE border="0" cellpadding="0" cellspacing="2" width="550" align="center">
								<TR>
									<TD class="emailFormLabel" align="right" width="30%" valign="top">
										<?=ARTICLE_TITLE?>
									</TD>
									<TD><?=getTitle($article->getTitle());?></TD>
								</TR>
								<TR>
									<TD height="15">
									</TD>
								</TR>
								<TR>
									<TD class="emailFormLabel" align="right" width="30%">
										<?=FIELD_PROFILE_NAME?>:
										<span id="fromNameLabel" class="tfvNormal">
											<?=FIELD_LAST_NAME_ERROR_DESCRIPTION?>
										</span>
									</TD>
									<TD>
										<INPUT type="text" name="fromName" size="40">
									</TD>
								</TR>
								<TR>
									<TD class="emailFormLabel" align="right">
										<?=FIELD_EMAIL?>:
										<span id="fromLabel" class="tfvNormal">
											<?=FIELD_EMAIL_ERROR_DESCRIPTION?>
										</span>
									</TD>
									<TD>										
										<INPUT type="text" name="from" size="40">
									</TD>
								</TR>
								<TR>
									<TD class="emailFormLabel" align="right">
										<?=TO_NAME?>
										<span id="toNameLabel" class="tfvNormal">
											<?=FIELD_LAST_NAME_ERROR_DESCRIPTION?>
										</span>
									</TD>
									<TD>

										<INPUT type="text" name="toName" size="40">
									</TD>
								</TR>
								<TR>
									<TD class="emailFormLabel" align="right">
										<?=TO_EMAIL?>
										<span id="toLabel" class="tfvNormal">
											<?=FIELD_LAST_NAME_ERROR_DESCRIPTION?>
										</span>
									</TD>
									<TD>										
										<INPUT type="text" name="to" size="40">
									</TD>
								</TR>
								<TR>
									<TD class="emailFormLabel" align="right" valign="top">
										<?=COMMENTS?>
									</TD>
									<TD>
										<TEXTAREA rows="4" cols="40" name="comment"></TEXTAREA>
									</TD>
								</TR>
								<TR>
									<TD height="10">
									</TD>
								</TR>
								<TR align="center">
									<TD colspan="2">										
										<INPUT type="submit" class="submit" value="<?=SEND?>">										
										<INPUT type="button" class="submit" value="<?=CLOSE?>" onclick="window.close();">
									</TD>
								</TR>								
								<INPUT type="hidden" name="form" value="1">								
								<INPUT type="hidden" name="id" value="24039">								
								<INPUT type="hidden" name="titulo" value="Resolu��o RE-20060922-3169-">
							</TABLE>
						<?}?>
						</FORM>
					</div>
					<div style="clear: both;float: none;width: 100%;">
					</div>
				</div>
			</div>
		</div>
			<script>
			var a_fields = {
				'fromName': {
					'l': 'fromName',  // label
					'r': true,    // required
					'f': 'text',  // format (see below)
					't': 'fromNameLabel',// id of the element to highlight if input not validated
					'm': null,     // must match specified form field
					'mn': 3,       // minimum length
					'mx': 100       // maximum length
				},
				'from': {
					'l': 'from',  // label
					'r': true,    // required
					'f': 'email',  // format (see below)
					't': 'fromLabel',// id of the element to highlight if input not validated
					'm': null,     // must match specified form field
					'mn': 3,       // minimum length
					'mx': 100       // maximum length
				},
				'toName': {
					'l': 'toName',  // label
					'r': true,    // required
					'f': 'text',  // format (see below)
					't': 'toNameLabel',// id of the element to highlight if input not validated
					'm': null,     // must match specified form field
					'mn': 3,       // minimum length
					'mx': 100       // maximum length
				},
				'to': {
					'l': 'to',  // label
					'r': true,    // required
					'f': 'email',  // format (see below)
					't': 'toLabel',// id of the element to highlight if input not validated
					'm': null,     // must match specified form field
					'mn': 3,       // minimum length
					'mx': 100       // maximum length
				}

			};

			o_config = {
				'to_disable' : [],
				'alert' : false
			};

			// validator constructor call
			var v = new validator('sendMail', a_fields, o_config);

			</script>
	</BODY>
</HTML>
