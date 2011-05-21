<div id="ngcomments_recaptcha_html" style="display:none">
	{def $fields = ezini( 'FormSettings', 'AvailableFields', 'ezcomments.ini' )}
	{if $fields|contains( 'recaptcha' )}
	    {def $bypass_captcha = fetch( 'comment', 'has_access_to_security', hash( 'limitation', 'AntiSpam', 'option_value', 'bypass_captcha' ) )}
		{if $bypass_captcha|not}
			{if ezini( 'RecaptchaSetting', 'PublicKey', 'ezcomments.ini' )|eq('')}
				<div class="message-warning">
					{'reCAPTCHA API key missing.'|i18n( 'ezcomments/comment/add' )}
				</div>
			{else}
				<script type="text/javascript">
				        {def $theme = ezini( 'RecaptchaSetting', 'Theme', 'ezcomments.ini' )}
				        {def $language = ezini( 'RecaptchaSetting', 'Language', 'ezcomments.ini' )}
				        {def $tabIndex = ezini( 'RecaptchaSetting', 'TabIndex', 'ezcomments.ini' )}
				        var RecaptchaOptions = {literal}{{/literal} theme : '{$theme}',
				                                 lang : '{$language}',
				                                 tabindex : {$tabIndex} {literal}}{/literal};
				</script>
				{if $theme|eq('custom')}
					{*Customized theme start*}
						<p>
							{'Enter both words below, with or without a space.'|i18n( 'ezcomments/comment/add/form' )}<br />
							{'The letters are not case-sensitive.'|i18n( 'ezcomments/comment/add/form' )}<br />
							{'Can\'t read this?'|i18n( 'ezcomments/comment/add/form' )}
							<a href="javascript:;" onclick="Recaptcha.reload();">
								{'Try another'|i18n( 'ezcomments/comment/add/form' )}
							</a>
							</p>
								<div id="recaptcha_image"></div>
							<p>
							<input type="text" class="box" id="recaptcha_response_field" name="recaptcha_response_field" />
						</p>
					{*Customized theme end*}
				{/if}
				{fetch( 'comment', 'recaptcha_html' )}
			{/if}
		{/if}
	{/if}
</div>