<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require 'vendor/autoload.php';

// $messages defined in the EmailFile.i18n.php

require_once __DIR__ . '/EmailFile.i18n.php';

class EmailFile extends SpecialPage {
	var $emailaddress;
	var $emailname;
	var $emailimage;
	var $emaildate;
	var $emailrights;
	var $emailcomments;
	var $emailfilename;
	var $emailtmpfilename;
	var $emailfiletype;
	var $emailfilesize;
	var $emailtype;
	var $emailsize;
	var $imglicense;
	var $imgliving;
	var $emailimgurl;
	var $imgfscollectionname;
	var $newlicensename;
	var $to;
	var $subject;

	function __construct() {
		global $wgEmailFileSubject, $wgEmailFileEmailAddress;

		parent::__construct( "EmailFile" );

		# Who gets this email and what is the subject?
		$this->to = $wgEmailFileEmailAddress;
	}

	function execute( $par ) {
		global $wgRequest, $wgOut;

		if ( self::checkPermissions() ) {
			# We passed all permission checks go ahead and email....
			$this->setHeaders();

			# Get request data from, e.g.
			$action = $wgRequest->getText( 'action' );

			$this->emailaddress = isset( $_REQUEST['emailaddress'] ) ? $_REQUEST['emailaddress'] : '';
			$this->emailname = isset( $_REQUEST['emailname'] ) ? $_REQUEST['emailname'] : '';
			$this->emailimage = isset( $_REQUEST['emailimage'] ) ? $_REQUEST['emailimage'] : '';
			$this->emaildate = isset( $_REQUEST['emaildate'] ) ? $_REQUEST['emaildate'] : '';
			$this->emailrights = isset( $_REQUEST['emailrights'] ) ? $_REQUEST['emailrights'] : '';
			$this->emailcomments = isset( $_REQUEST['emailcomments'] ) ? $_REQUEST['emailcomments'] : '';
			$this->emailfilename = isset( $_FILES['emailfilename']['name'] ) ? $_FILES['emailfilename']['name'] : '';
			$this->emailtmpfilename = isset( $_FILES['emailfilename']['tmp_name'] ) ? $_FILES['emailfilename']['tmp_name'] : '';
			$this->emailtype = isset( $_FILES['emailfilename']['type'] ) ? $_FILES['emailfilename']['type'] : '';
			$this->emailsize = isset( $_FILES['emailfilename']['size'] ) ? $_FILES['emailfilename']['size'] : '';
			$this->imglicense = isset( $_REQUEST['imglicense'] ) ? $_REQUEST['imglicense'] : '';
			$this->imgliving = isset( $_REQUEST['imgliving'] ) ? $_REQUEST['imgliving'] : '';
			$this->emailimgurl = isset( $_REQUEST['emailimgurl'] ) ? $_REQUEST['emailimgurl'] : '';
			$this->imgfscollectionname = isset( $_REQUEST['imgfscollectionname'] ) ? $_REQUEST['imgfscollectionname'] : '';
			$this->newlicensename = isset( $_REQUEST['newlicensename'] ) ? $_REQUEST['newlicensename'] : '';

			if ( $action == 'submit' && $wgRequest->wasPosted() ) {
				if ( self::validateForm() ) {
					self::sendPHPMailerEmail();
				}
			} elseif ( $action == "success" ) {
				self::showSuccess();
			} else {
				self::showForm( $action );
			}
		}
	}

	function validateForm() {
		global $wgOut, $wfMsg, $wgUser;

		if ( empty( $this->emailrights ) ) {
			self::showForm( true, wfMessage( 'rightserror' )->inContentLanguage()->text(), 'emailcertify' );
			return false;
		} elseif ( !file_exists( $this->emailtmpfilename ) ) {
			self::showForm( true, wfMessage( 'fileerror' )->inContentLanguage()->text(), 'emailfilename' );
			return false;
		} elseif ( empty( $this->imglicense ) ) {
			// image license are has nothing selected
			self::showForm( true, wfMessage( 'imglicenseerror' )->inContentLanguage()->text(), 'imglicensetable' );
			return false;
		} elseif ( $this->imglicense == 'imginternet' && !filter_var( $this->emailimgurl, FILTER_VALIDATE_URL ) ) {
			// make sure to have a valid image url
			self::showForm( true, wfMessage( 'imginterneterror' )->inContentLanguage()->text(), 'imglicensetable' );
			return false;
		} elseif ( $this->imglicense == 'imgfscollection' && empty( $this->imgfscollectionname ) ) {
			// make sure to have a family search collection name
			self::showForm( true, wfMessage( 'imgfscolnameerror' )->inContentLanguage()->text(), 'imglicensetable' );
			return false;
		} elseif ( $this->imglicense == 'imgnewlicense' && empty( $this->newlicensename ) ) {
			// make sure to have a license name
			self::showForm( true, wfMessage( 'imglicnameerror' )->inContentLanguage()->text(), 'imglicensetable' );
			return false;
		} elseif ( empty( $this->imgliving ) ) {
			self::showForm( true, wfMessage( 'imglivingerror' )->inContentLanguage()->text(), 'imglivingtable' );
			return false;
		}

		return true;
	}

	function showForm( $showErr = false, $error_msg = '', $error_field_id = '' ) {
		global $wgOut, $wfMsg, $wgUser;

		# Get the data necessary to create the appropriate action statement.
		$special = Title::makeTitle( NS_SPECIAL, 'EmailFile' );
		$action = $special->getLocalURL( 'action=submit' );

		# Get the users information to preload the fields.
		$senderEmail = $wgUser->getEmail();
		$senderName = $wgUser->getRealName();

		# Create the form buffer and replace with appropriate localized text.
		$form = '<h2><span class="mw-headline">' . wfMessage( 'labelemailheader1' )->inContentLanguage()->text() . '</span></h2>';

		if ( $showErr ) {
			$form .= '<div style="color:red;padding-top:10px;padding-bottom:10px">' . $error_msg . '</div>';
			if ( !empty( $error_field_id ) ) {
				$form .= '<script>$(function(){$("#' . $error_field_id . '").css({"border-width":"1px","border-color":"red", "border-style":"solid"})});</script>';
			}
		}

		$form .= '<form id="emailfileform" action="' . $action . '" method="post" enctype="multipart/form-data">
	<table border="0" cellspacing="2" cellpadding="0">
	<tr>
		<td><label for="emailaddress">' . wfMessage( 'labelemailaddress' )->inContentLanguage()->text() . '</label></td>
		<td><input id="emailaddress" type="text" name="emailaddress" size="55" value="' . $senderEmail . '"/></td>
	</tr>
	<tr>
		<td><label for="emailname">' . wfMessage( 'labelemailname' )->inContentLanguage()->text() . '</label></td>
		<td><input id="emailname" type="text" name="emailname" size="55" value="' . $senderName . '"/></td>
	</tr>
	<tr>
		<td valign="top"><label for="emailfilename">' . wfMessage( 'labelemailfile' )->inContentLanguage()->text() . '</label></td>
		<td><input id="emailfilename" type="file" name="emailfilename" size="24" value="' . $this->emailfilename . '"/></td>
	</tr>
	<tr>
		<td><label for="emailimage">' . wfMessage( 'labelemailimage' )->inContentLanguage()->text() . '</label></td>
		<td><input id="emailimage" type="text" name="emailimage" size="55" value="' . $this->emailimage . '" /></td>
	</tr>
	<tr>
		<td><label for="emaildate">' . wfMessage( 'labelemaildate' )->inContentLanguage()->text() . '</label></td>
		<td><input id="emaildate" type="text" name="emaildate" size="55" value="' . $this->emaildate . '" /></td>
	</tr>
	<tr>
		<td style="vertical-align: top"><label for="emailcomments">' . wfMessage( 'labelemailcomments' )->inContentLanguage()->text() . '</label></td>
		<td><textarea id="emailcomments" name="emailcomments" rows="4" cols="40">' . $this->emailcomments . '</textarea></td>
	</tr>';

		// checkbox to certify
		$form .= '
			<tr>
				<td id="emailcertify" colspan="2"><input type="checkbox" name="emailrights" value="Has the right to distribute">' . wfMessage( 'labelemailrightdist' )->inContentLanguage()->text() . '</td>
			</tr>
		</table>';

		// section 2
		$form .= '<table id="imglicensetable"><tr><td><h2><span class="mw-headline">' . wfMessage( 'labelemailheader2' )->inContentLanguage()->text() . '</span></h2></td></tr>';
		$form .= '<tr>
							<td><input type="radio" name="imglicense" value="imginternet">' . wfMessage( 'labelimginternet' )->inContentLanguage()->text() . '</input></td>
						</tr>
						<tr>
							<td>' . wfMessage( 'labelimgurl' )->inContentLanguage()->text() . '<input type="text" name="emailimgurl" value="' . $this->emailimgurl . '" /></td>
						</tr>
						<tr>
							<td><input type="radio" name="imglicense" value="imgfscollection">' . wfMessage( 'labelimgfscollection' )->inContentLanguage()->text() . '</input></td>
						</tr>
						<tr>
							<td>' . wfMessage( 'labelimgfscollectionname' )->inContentLanguage()->text() . '<input type="text" name="imgfscollectionname" value="' . $this->imgfscollectionname . '" /></td>
						</tr>
						<tr>
							<td><input type="radio" name="imglicense" value="imgowner">' . wfMessage( 'labelimgowner' )->inContentLanguage()->text() . '</input></td>
						</tr>
						<tr>
							<td><input type="radio" name="imglicense" value="imgorg">' . wfMessage( 'labelimgorg' )->inContentLanguage()->text() . '</input></td>
						</tr>
						<tr>
							<td><input type="radio" name="imglicense" value="imgnewlicense">' . wfMessage( 'labelnewlicense' )->inContentLanguage()->text() . '</input></td>
						</tr>
						<tr>
							<td><input type="text" name="newlicensename" value="' . $this->newlicensename . '" /></td>
						</tr>
						<tr>
							<td>' . wfMessage( 'labeladvusers' )->inContentLanguage()->text() . '</td>
						</tr>
						</table>';

		// section 3
		$form .= '<h2><span class="mw-headline">' . wfMessage( 'labelemailheader3' )->inContentLanguage()->text() . '</span></h2>';
		$form .= '<h6><span>' . wfMessage( 'labelemailheader4' )->inContentLanguage()->text() . '</span></h6>';
		$form .= '<table id="imglivingtable">
						<tr>
						<td colspan="2"><input type="radio" name="imgliving" value="noliving" />' . wfMessage( 'labelnoliving' )->inContentLanguage()->text() . '</td>
						</tr>
						<tr>
						<td colspan="2"><input type="radio" name="imgliving" value="headshot" />' . wfMessage( 'labelheadshot' )->inContentLanguage()->text() . '</td>
						</tr>
						<tr>
						<td colspan="2"><input type="radio" name="imgliving" value="nofeatures" />' . wfMessage( 'labelnofeatures' )->inContentLanguage()->text() . '</td>
						</tr>
						<tr>
						<td colspan="2"><input type="radio" name="imgliving" value="organization" />' . wfMessage( 'labelorganization' )->inContentLanguage()->text() . '</td>
						</tr>
						</table>
						<hr/>
						<input type="submit" value="' . wfMessage( 'buttonemailsend' )->inContentLanguage()->text() . '" />';

		// handle the img license radio buttons when the form is posted to keep the value selected
		if ( !empty( $this->imglicense ) ) {
			$form .= '<script>$(function(){$("input[value=\'' . $this->imglicense . '\']").attr(\'checked\', \'checked\');})</script>';
		}

		// handle the image living radio buttons when the form is posted ot keep the value selected
		if ( !empty( $this->imgliving ) ) {
			$form .= '<script>$(function(){$("input[value=\'' . $this->imgliving . '\']").attr(\'checked\', \'checked\');})</script>';
		}

		// complete the form
		$form .= '</form>';

		# Show the form just constructed.
		$wgOut->addHTML( $form );
	}

	function sendPHPMailerEmail() {
		global $wgOut, $wgUser, $wgSitename, $wgLang;

		// These are set in LocalSettings.php
		global $wgEmailFileEmailAddress, $wgEmailFileSubject;

		// Ensure valid values in these variables.
		if ( $wgEmailFileEmailAddress == '' ) {
			die( 'Can not send. Please set $wgEmailFileEmailAddress variable!' );
		}

		# If the upload worked the file should exist.
		if ( file_exists( $this->emailtmpfilename ) ) {
			# Make sure it is an uploaded file and not a system file.
			if ( is_uploaded_file( $this->emailtmpfilename ) ) {

				$message = '';
				$message .= 'Original filename: ' . $this->emailfilename . "\n";
				$message .= wfMessage( 'labelemailaddress' )->inContentLanguage()->text() . " $this->emailaddress\n";
				$message .= wfMessage( 'labelemailname' )->inContentLanguage()->text() . " $this->emailname\n";
				$message .= wfMessage( 'labelemailimage' )->inContentLanguage()->text() . " $this->emailimage\n";
				$message .= wfMessage( 'labelemaildate' )->inContentLanguage()->text() . " $this->emaildate\n";
				$message .= wfMessage( 'labelemailcomments' )->inContentLanguage()->text() . " $this->emailcomments\n";

				// license information
				$message .= "License Information\n";
				if ( $this->imglicense == 'imginternet' ) {
					$message .= wfMessage( 'labelimginternet' )->inContentLanguage()->text() . "\n";
					$message .= wfMessage( 'labelimgurl' )->inContentLanguage()->text() . $this->emailimgurl . "\n";
				} elseif ( $this->imglicense == 'imgfscollection' ) {
					$message .= wfMessage( 'labelimgfscollectionname' )->inContentLanguage()->text() . $this->imgfscollectionname . "\n";
				} elseif ( $this->imglicense == 'imgowner' ) {
					$message .= wfMessage( 'labelimgowner' )->inContentLanguage()->text() . "\n";
				} elseif ( $this->imglicense == 'imgorg' ) {
					$message .= wfMessage( 'labelimgorg' )->inContentLanguage()->text() . "\n";
				} elseif ( $this->imglicense == 'imgnewlicense' ) {
					$message .= "License name: " . $this->newlicensename . "\n";
				}

				// living persons information
				$message .= "Living Persons Information\n";
				if ( $this->imgliving == 'noliving' ) {
					$message .= wfMessage( 'labelnoliving' )->inContentLanguage()->text() . "\n";
				} elseif ( $this->imgliving == 'headshot' ) {
					$message .= wfMessage( 'labelheadshot' )->inContentLanguage()->text() . "\n";
				} elseif ( $this->imgliving == 'nofeatures' ) {
					$message .= wfMessage( 'labelnofeatures' )->inContentLanguage()->text() . "\n";
				} elseif ( $this->imgliving == 'organization' ) {
					$message .= wfMessage( 'labelorganization' )->inContentLanguage()->text() . "\n";
				}

				// wiki name
				$message .= "WIKI: $wgSitename ($wgLang->mCode)\n";
				$message .= "\n";

				try {
					// Instantiation and passing `true` enables exceptions
					$mail = new PHPMailer( true );
					$mail->setFrom( 'no-reply@familysearch.org', 'No Reply' );
					$mail->addReplyTo( $this->emailaddress, $this->emailname );
					// $mail->addBcc( 'batsondl@familysearch.org' );
					$mail->addAddress( $wgEmailFileEmailAddress );
					$mail->Subject = $this->subject = "$wgEmailFileSubject - " . $wgLang->mCode . " wiki";
					$mail->Body = $message;
					$mail->addAttachment( $this->emailtmpfilename, $this->emailfilename );
					$mail->send();
					self::showSuccess();

				} catch ( Exception $e ) {
					echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
					self::showFailure();
				}
			}
		}
	}

	function showSuccess() {
		global $wgOut, $wfMsg;
		$wgOut->addHTML( "<p>" . wfMessage( 'emailsuccess' )->inContentLanguage()->text() . "</p>" );
	}

	function showFailure() {
		global $wgOut;
		$wgOut->addHTML( "<p>" . wfMessage( 'emailfailure' )->inContentLanguage()->text() . "</p>" );
	}

	// FHS-4845 - fixing issues with using $messages as a global value
	// TODO FHS-6025 - determine if this method is needed
	function loadMessages( $title, &$message ) {
		return true;
	}

	function checkPermissions() {
		# True is the user passed the permissions check, otherwise false.
		global $wgEnableEmail, $wgEnableUserEmail, $wgUser, $wgOut;

		# Check email and user permissions for security.
		if ( !( $wgEnableEmail && $wgEnableUserEmail ) ) {
			$wgOut->showErrorPage( "emailnotenabled", "emailnotenabledtext" );
			return false;
		}

		if ( !$wgEnableEmail ) {
			$wgOut->showErrorPage( "emailnotenabled", "emailnotenabledtext" );
			return false;
		}

		if ( !$wgUser->canSendEmail() ) {
			wfDebug( "User can't send.\n" );
			$wgOut->showErrorPage( "mailnologin", "mailnologintext" );
			return false;
		}

		if ( $wgUser->isBlockedFromEmailUser() ) {
			// User has been blocked from sending e-mail. Show the std blocked form.
			wfDebug( "User is blocked from sending e-mail.\n" );
			$wgOut->blockedPage();
			return false;
		}

		return true;
	}

	/**
	 * Override the parent to set where the special page appears on Special:SpecialPages
	 * 'other' is the default. If that's what you want, you do not need to override.
	 * Specify 'media' to use the <code>specialpages-group-media</code> system interface message, which translates to 'Media reports and uploads' in English;
	 *
	 * @return string
	 */
	function getGroupName() {
		return 'pagetools';
	}
}
