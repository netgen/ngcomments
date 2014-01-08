{ezscript_require( array( 'jquery.ngComments.js' ) )}

{include uri="design:comment/recaptcha.tpl"}
<a name="comments"></a>
<div id="comment-container" class="comment-container">
    <input type="hidden" name="MissingInputMessage" value="{'Some fields are missing input!'|i18n( 'ngcomments/comment' )}" />
    <input type="hidden" name="DeleteCommentMessage" value="{'Delete comment?'|i18n( 'ezcomments/comment/delete' )}" />
    <input type="hidden" name="DeleteButtonText" value="{'Delete'|i18n( 'ezcomments/comment/view' )}" />
    <input type="hidden" name="EditButtonText" value="{'Edit'|i18n( 'ezcomments/comment/view' )}" />
    <input type="hidden" name="ObjectAttributeID" value="{$attribute.id}" />
    <input type="hidden" name="Version" value="{$attribute.version}" />
    <div class="initial-comments-loader">
        <div class="comments-loading">
            <p>{'Loading comments ...'|i18n( 'ngcomments/comment' )}</p>
            <img src={'ajax-loader-bar-white.gif'|ezimage} alt="{'Loading comments ...'|i18n( 'ngcomments/comment' )}" />
        </div>
    </div>
</div>

<script type="text/javascript">
{literal}
    jQuery(document).ready(function(){
        jQuery.ez('ezjscNgComments::commentList', {'attribute_id': '{/literal}{$attribute.id}{literal}',
                'version': '{/literal}{$attribute.version}{literal}', 'page': '0', 'is_reload': '0'}, function(data){
            jQuery('.initial-comments-loader').remove();
            if(data.content.status == 'success') {
                jQuery('#comment-container').append(data.content.content).ngComments({
                    ajax_loader_path: "{/literal}{'images/ajax-loader-comments.gif'|ezdesign(no)}{literal}"
                });
            }
            else {
                jQuery('#comment-container').append(data.content.message);
            }
        });
    });
{/literal}
</script>
