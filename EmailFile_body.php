<?php
# Extension:EmailFile
# - Intellectual Reserve, Inc.(c) 2008
# - Author: Don B. Stringham (stringhamdb@ldschurch.org)
# - Started: 04-21-2008
# - Form refactored: 06-20-2008
# - Globalized: 10-27-2010

// $messages defined in the EmailFile.i18n.php

	require_once(dirname(__FILE__) . '/EmailFile.i18n.php');

	class EmailFile extends SpecialPage
	{
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

		function __construct()
		{
			global $wgEmailFileSubject, $wgEmailFileEmailAddress;

			parent::__construct("EmailFile");

			# Who gets this email and what is the subject?
			$this->to = $wgEmailFileEmailAddress;
		}

		function execute($par)
		{
			global $wgRequest, $wgOut;

			if (self::checkPermissions()) {
				# We passed all permission checks go ahead and email....
				$this->setHeaders();

				# Get request data from, e.g.
				$action = $wgRequest->getText('action');

				$this->emailaddress = isset($_REQUEST['emailaddress']) ? $_REQUEST['emailaddress'] : '';
				$this->emailname = isset($_REQUEST['emailname']) ? $_REQUEST['emailname'] : '';
				$this->emailimage = isset($_REQUEST['emailimage']) ? $_REQUEST['emailimage'] : '';
				$this->emaildate = isset($_REQUEST['emaildate']) ? $_REQUEST['emaildate'] : '';
				$this->emailrights = isset($_REQUEST['emailrights']) ? $_REQUEST['emailrights'] : '';
				$this->emailcomments = isset($_REQUEST['emailcomments']) ? $_REQUEST['emailcomments'] : '';
				$this->emailfilename = isset($_FILES['emailfilename']['name']) ? $_FILES['emailfilename']['name'] : '';
				$this->emailtmpfilename = isset($_FILES['emailfilename']['tmp_name']) ? $_FILES['emailfilename']['tmp_name'] : '';
				$this->emailtype = isset($_FILES['emailfilename']['type']) ? $_FILES['emailfilename']['type'] : '';
				$this->emailsize = isset($_FILES['emailfilename']['size']) ? $_FILES['emailfilename']['size'] : '';
				$this->imglicense = isset($_REQUEST['imglicense']) ? $_REQUEST['imglicense'] : '';
				$this->imgliving = isset($_REQUEST['imgliving']) ? $_REQUEST['imgliving'] : '';
				$this->emailimgurl = isset($_REQUEST['emailimgurl']) ? $_REQUEST['emailimgurl'] : '';
				$this->imgfscollectionname = isset($_REQUEST['imgfscollectionname']) ? $_REQUEST['imgfscollectionname'] : '';
				$this->newlicensename = isset($_REQUEST['newlicensename']) ? $_REQUEST['newlicensename'] : '';

				if ($action == 'submit' && $wgRequest->wasPosted()) {
					if (self::validateForm()) {
						self::sendEmail();
					}
				} else if ($action == "success") {
					self::showSuccess();
				} else {
					self::showForm($action);
				}
			}
		}

		function validateForm()
		{
			global $wgOut, $wfMsg, $wgUser;

			if (empty($this->emailrights)) {
				self::showForm(true, wfMessage( 'rightserror' )->inContentLanguage()->text(), 'emailcertify');
				return false;
			} else if (!file_exists($this->emailtmpfilename)) {
				self::showForm(true, wfMessage( 'fileerror' )->inContentLanguage()->text(), 'emailfilename');
				return false;
			} else if (empty($this->imglicense)) {
				// image license are has nothing selected
				self::showForm(true, wfMessage( 'imglicenseerror' )->inContentLanguage()->text(), 'imglicensetable');
				return false;
			} else if ($this->imglicense == 'imginternet' && !filter_var($this->emailimgurl, FILTER_VALIDATE_URL)) {
				// make sure to have a valid image url
				self::showForm(true, wfMessage( 'imginterneterror' )->inContentLanguage()->text(), 'imglicensetable');
				return false;
			} else if ($this->imglicense == 'imgfscollection' && empty($this->imgfscollectionname)) {
				// make sure to have a family search collection name
				self::showForm(true, wfMessage( 'imgfscolnameerror' )->inContentLanguage()->text(), 'imglicensetable');
				return false;
			} else if ($this->imglicense == 'imgnewlicense' && empty($this->newlicensename)) {
				// make sure to have a license name
				self::showForm(true, wfMessage( 'imglicnameerror' )->inContentLanguage()->text(), 'imglicensetable');
				return false;
			} else if (empty($this->imgliving)) {
				self::showForm(true, wfMessage( 'imglivingerror' )->inContentLanguage()->text(), 'imglivingtable');
				return false;
			}

			return true;
		}

		function showForm($showErr = false, $error_msg = '', $error_field_id = '')
		{
			global $wgOut, $wfMsg, $wgUser;

			# Get the data necessary to create the appropriate action statement.
			$special = Title::makeTitle(NS_SPECIAL, 'EmailFile');
			$action = $special->getLocalURL('action=submit');

			# Get the users information to preload the fields.
			$senderEmail = $wgUser->getEmail();
			$senderName = $wgUser->getRealName();

			# Create the form buffer and replace with appropriate localized text.
			$form = '<h2><span class="mw-headline">' . wfMessage( 'labelemailheader1' )->inContentLanguage()->text() . '</span></h2>';

			if ($showErr) {
				$form .= '<div style="color:red;padding-top:10px;padding-bottom:10px">' . $error_msg . '</div>';
				if (!empty($error_field_id)) {
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
					<td id="emailcertify" colspan="2"><input type="checkbox" name="emailrights" value="Has the right to distribute">' . wfMessage('labelemailrightdist'  )->inContentLanguage()->text() . '</td>
				</tr>
			</table>';

			// section 2
			$form .= '<table id="imglicensetable"><tr><td><h2><span class="mw-headline">' . wfMessage('labelemailheader2'  )->inContentLanguage()->text() . '</span></h2></td></tr>';
			$form .= '<tr>
								<td><input type="radio" name="imglicense" value="imginternet">' . wfMessage('labelimginternet'  )->inContentLanguage()->text() . '</input></td>
							</tr>
							<tr>
								<td>' . wfMessage('labelimgurl'  )->inContentLanguage()->text() . '<input type="text" name="emailimgurl" value="' . $this->emailimgurl . '" /></td>
							</tr>
							<tr>
								<td><input type="radio" name="imglicense" value="imgfscollection">' . wfMessage('labelimgfscollection'  )->inContentLanguage()->text() . '</input></td>
							</tr>
							<tr>
								<td>' . wfMessage('labelimgfscollectionname'  )->inContentLanguage()->text() . '<input type="text" name="imgfscollectionname" value="' . $this->imgfscollectionname . '" /></td>
							</tr>
							<tr>
								<td><input type="radio" name="imglicense" value="imgowner">' . wfMessage('labelimgowner'  )->inContentLanguage()->text() . '</input></td>
							</tr>
							<tr>
								<td><input type="radio" name="imglicense" value="imgorg">' . wfMessage('labelimgorg'  )->inContentLanguage()->text() . '</input></td>
							</tr>
							<tr>
								<td><input type="radio" name="imglicense" value="imgnewlicense">' . wfMessage('labelnewlicense'  )->inContentLanguage()->text() . '</input></td>
							</tr>
							<tr>
								<td><input type="text" name="newlicensename" value="' . $this->newlicensename . '" /></td>
							</tr>
							<tr>
								<td>' . wfMessage('labeladvusers'  )->inContentLanguage()->text() . '</td>
							</tr>
							</table>';

			// section 3
			$form .= '<h2><span class="mw-headline">' . wfMessage('labelemailheader3'  )->inContentLanguage()->text() . '</span></h2>';
			$form .= '<h6><span>' . wfMessage('labelemailheader4'  )->inContentLanguage()->text() . '</span></h6>';
			$form .= '<table id="imglivingtable">
							<tr>
							<td colspan="2"><input type="radio" name="imgliving" value="noliving" />' . wfMessage('labelnoliving'  )->inContentLanguage()->text() . '</td>
							</tr>
							<tr>
							<td colspan="2"><input type="radio" name="imgliving" value="headshot" />' . wfMessage('labelheadshot'  )->inContentLanguage()->text() . '</td>
							</tr>
							<tr>
							<td colspan="2"><input type="radio" name="imgliving" value="nofeatures" />' . wfMessage('labelnofeatures'  )->inContentLanguage()->text() . '</td>
							</tr>
							<tr>
							<td colspan="2"><input type="radio" name="imgliving" value="organization" />' . wfMessage('labelorganization'  )->inContentLanguage()->text() . '</td>
							</tr>
							</table>
							<hr/>
							<input type="submit" value="' . wfMessage('buttonemailsend'  )->inContentLanguage()->text() . '" />';

			// handle the img license radio buttons when the form is posted to keep the value selected
			if (!empty($this->imglicense)) {
				$form .= '<script>$(function(){$("input[value=\'' . $this->imglicense . '\']").attr(\'checked\', \'checked\');})</script>';
			}

			// handle the image living radio buttons when the form is posted ot keep the value selected
			if (!empty($this->imgliving)) {
				$form .= '<script>$(function(){$("input[value=\'' . $this->imgliving . '\']").attr(\'checked\', \'checked\');})</script>';
			}

			// complete the form
			$form .= '</form>';

			# Show the form just constructed.
			$wgOut->addHTML($form);
		}

		function sendEmail()
		{
			global $wgOut, $wgUser, $wgSitename, $wgLang, $wgPasswordSender;
			
			// These are set in LocalSettings.php
			global $wgEmailFileEmailAddress, $wgEmailFileSubject;

			// Ensure valid values in these variables.
			if ($wgEmailFileEmailAddress == '') {
				die('Can not send. Please set $wgEmailFileEmailAddress variable!');
			}

			# If the upload worked the file should exist.
			if (file_exists($this->emailtmpfilename)) {
				# Make sure it is an uploaded file and not a system file.
				if (is_uploaded_file($this->emailtmpfilename)) {
					# Open the file in binary mode.
					$file = fopen($this->emailtmpfilename, 'rb');
					# Read the file into a memory buffer.
					$data = fread($file, filesize($this->emailtmpfilename));
					# Close it.
					fclose($file);
					# Encode and split it up to acceptable length lines.
					$data = chunk_split(base64_encode($data));
					# generate a random string to be used as the boundary marker
					//$mime_boundary="==Multipart_Boundary_x".md5(mt_rand())."x";
					$mime_boundary = '<<<--==+X[' . md5(time()) . ']';

					// A simple message in case the mail reader cannot process MIME
					$message = "This is a MIME encoded message.\r\n";

					// Build the main message body here
					$message .= '--' . $mime_boundary . "\r\n";
					$message .= 'Content-Type: text/plain; charset="UTF-8"' . "\r\n";
					$message .= wfMessage( 'labelemailaddress')->inContentLanguage()->text() . $this->emailaddress . "\r\n";
					$message .= wfMessage( 'labelemailname')->inContentLanguage()->text() . " $this->emailname\r\n";
					$message .= wfMessage( 'labelemailimage')->inContentLanguage()->text() . " $this->emailimage\r\n";
					$message .= wfMessage( 'labelemaildate')->inContentLanguage()->text() . " $this->emaildate\r\n";
					$message .= wfMessage( 'labelemailcomments')->inContentLanguage()->text() . " $this->emailcomments\r\n";

					// license information
					$message .= "License Information\r\n";
					if ($this->imglicense == 'imginternet') {
						$message .= wfMessage( 'labelimginternet')->inContentLanguage()->text() . "\r\n";
						$message .= wfMessage( 'labelimgurl')->inContentLanguage()->text() . $this->emailimgurl . "\r\n";
					} else if ($this->imglicense == 'imgfscollection') {
						$message .= wfMessage( 'labelimgfscollectionname')->inContentLanguage()->text() . $this->imgfscollectionname . "\r\n";
					} else if ($this->imglicense == 'imgowner') {
						$message .= wfMessage( 'labelimgowner')->inContentLanguage()->text() . "\r\n";
					} else if ($this->imglicense == 'imgorg') {
						$message .= wfMessage( 'labelimgorg')->inContentLanguage()->text() . "\r\n";
					} else if ($this->imglicense == 'imgnewlicense') {
						$message .= "License name: " . $this->newlicensename . "\r\n";
					}

					// living persons information
					$message .= "Living Persons Information\r\n";
					if ($this->imgliving == 'noliving') {
						$message .= wfMessage( 'labelnoliving')->inContentLanguage()->text() . "\r\n";
					} else if ($this->imgliving == 'headshot') {
						$message .= wfMessage( 'labelheadshot')->inContentLanguage()->text() . "\r\n";
					} else if ($this->imgliving == 'nofeatures') {
						$message .= wfMessage( 'labelnofeatures')->inContentLanguage()->text() . "\r\n";
					} else if ($this->imgliving == 'organization') {
						$message .= wfMessage( 'labelorganization')->inContentLanguage()->text() . "\r\n";
					}

					// wiki name
					$message .= "WIKI: $wgSitename\r\n";
					$message .= "\r\n";

					// Insert boundary to indicate start of attachment.
					$message .= '--' . $mime_boundary . "\r\n";

					// These two lines go together without a "\r\n"
					$message .= "Content-Type: application/octet-stream; ";
					$message .= 'name="' . $this->emailfilename . '"' . "\r\n";

					// Ditto these two lines
					$message .= "Content-Disposition: attachment; ";
					$message .= 'filename="' . $this->emailfilename . '"' . "\r\n";

					// All these lines need "\r\n"
					$message .= "Content-Transfer-Encoding: base64\r\n";
					$message .= "\r\n";

					// Include the base 64 encoded file here
					$message .= "$data\r\n";
					$message .= "\r\n";

					// And finally close the mime type and end the message
					$message .= '--' . $mime_boundary . "\r\n";
					$message .= "\r\n";

					// Build the message headers.
					$from = $this->emailname;
					$fromAddress = $this->emailaddress;

					// The To: is specified in the mail function below
					// $headers = "From: $from <$fromAddress>\r\n";
					$headers = "From: $wgPasswordSender\r\n";
					$headers .= "Bcc: greg@equality-tech.com, greg@rundlett.com\r\n";
					$headers .= "MIME-Version: 1.0\r\n";
					$headers .= "Content-Type: multipart/mixed; boundary=\"$mime_boundary\"\r\n";

					wfDebug("EmailFile is composing a message");

					// Add the language code for multi lingual routing
					$this->subject = "$wgEmailFileSubject - " . $wgLang->mCode;
					
					// $options = ['headers'=>$headers];
					// $to = $wgEmailFileEmailAddress;
					// $status = UserMailer::send($to, $fromAddress, $this->subject, $message);

					// if ($status) {
					// if (mail($wgEmailFileEmailAddress, $this->subject, $message, $headers)) {
					// if (mail($wgEmailFileEmailAddress, "Document Approval", "Testing")) {
					if ( UserMailer::send('greg@equality-tech.com', 'no-reply@msg.familysearch.org', 'document approval', 'Testing' ) ) {
						self::showSuccess();
						wfDebug("Sending mail to $wgEmailFileEmailAddress");
						$wgOut->addHTML("<div>Sending to $wgEmailFileEmailAddress</div>\r\n");
						$wgOut->addHTML("<div>Using these headers</div><pre>$headers</pre>\r\n");
					} else {
						self::showFailure();
					}
				}
			}

		}

		function showSuccess()
		{
			global $wgOut, $wfMsg;
			$wgOut->addHTML("<p>" . wfMessage('emailsuccess'  )->inContentLanguage()->text() . "</p>");
		}

		function showFailure()
		{
			global $wgOut;
			$wgOut->addHTML("<p>" . wfMessage('emailfailure'  )->inContentLanguage()->text() . "</p>");
		}

		function showRightsError()
		{
			global $wgOut;
			$wgOut->addHTML("<p>" . wfMessage('emailrightserror'  )->inContentLanguage()->text() . "</p>");
		}

		// FHS-4845 - fixing issues with using $messages as a global value
		// TODO FHS-6025 - determine if this method is needed
		function loadMessages($title, &$message)
		{
			return true;
		}

		function checkPermissions()
		{
			# True is the user passed the permissions check, otherwise false.
			global $wgEnableEmail, $wgEnableUserEmail, $wgUser, $wgOut;

			# Check email and user permissions for security.
			if (!($wgEnableEmail && $wgEnableUserEmail)) {
				$wgOut->showErrorPage("nosuchspecialpage", "nospecialpagetext");
				return false;
			}

			if (!$wgEnableEmail) {
				$wgOut->showErrorPage("nosuchspecialpage", "nospecialpagetext");
				return false;
			}

			if (!$wgUser->canSendEmail()) {
				wfDebug("User can't send.\r\n");
				$wgOut->showErrorPage("mailnologin", "mailnologintext");
				return false;
			}

			if ($wgUser->isBlockedFromEmailUser()) {
				// User has been blocked from sending e-mail. Show the std blocked form.
				wfDebug("User is blocked from sending e-mail.\r\n");
				$wgOut->blockedPage();
				return false;
			}

			return true;
		}
	}
