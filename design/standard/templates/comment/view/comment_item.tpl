<div class="ezcom-view-comment{if $comment.user_id} uoid{$comment.user_id}{/if}">
    <div class="ezcom-view-comment-item">
        <input type="hidden" name="CommentID" value="{$comment.id}" />
        <div class="separator">
            <div class="separator-design">
            </div>
        </div>
        <div class="attribute-byline ezcom-comment-top">
            {if $comment.name}
                <div class="ezcom-comment-author">
                    <p class="author">
                        {if $comment.url|eq( '' )}
                            {$comment.name|wash}
                        {else}
                            <a href="{$comment.url|wash}">
                                {$comment.name|wash}
                            </a>
                        {/if}
                    </p>
                    <span>
                     {'wrote:'|i18n('ezcomments/comment/view')}
                    </span>
                </div>
            {/if}
            <div class="ezcom-comment-time">
                <p class="date">
                    {$comment.created|l10n( 'shortdatetime' )}
                </p>
            </div>
        </div>
        {if $comment.title}
            <div class="ezcom-comment-title">
                    <span>
                        {$comment.title|wash}
                    </span>
            </div>
        {/if}
        <div class="ezcom-comment-body">
            <p>{$comment.text|wash|nl2br}</p>
        </div>
    </div>
</div>