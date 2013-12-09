<?php

class ezjscNgComments extends ezjscServerFunctions
{
    public static function commentList( $args )
    {
        $http = eZHTTPTool::instance();

        if ( $http->hasPostVariable( 'attribute_id' ) && $http->hasPostVariable( 'version' ) )
        {
            $objectAttributeID = (int) trim( $http->postVariable( 'attribute_id' ) );
            $objectAttributeVersion = (int) trim( $http->postVariable( 'version' ) );

            if ( $objectAttributeID > 0 && $objectAttributeVersion > 0 )
            {
                $objectAttribute = eZContentObjectAttribute::fetch( $objectAttributeID, $objectAttributeVersion );

                if ( $objectAttribute instanceof eZContentObjectAttribute )
                {
                    $page = (int) trim( $http->postVariable( 'page' ) );
                    $isReload = (int) trim( $http->postVariable( 'is_reload' ) );

                    $tpl = eZTemplate::factory();
                    $tpl->setVariable( 'attribute', $objectAttribute );
                    $tpl->setVariable( 'page', $page >= 0 ? $page : 0 );
                    $tpl->setVariable( 'is_reload', $isReload > 0 ? true : false );

                    return array( 'status' => 'success',
                                  'content' => $tpl->fetch( 'design:comment/comment_list.tpl' ) );
                }
            }
        }

        return array( 'status' => 'error',
                      'message' => ezpI18n::tr( 'ngcomments/comment/ezjsc', 'Error loading comment list!' ) );
    }

    public static function addComment( $args )
    {
        $http = eZHTTPTool::instance();
        $ezCommentsINI = eZINI::instance('ezcomments.ini');

        $formTool = ezcomAddCommentTool::instance();
        $formStatus = $formTool->checkVars();
        if ( !$formStatus )
        {
            return array( 'status' => 'error',
                          'message' => implode( "\n", array_values( $formTool->messages() ) ) );
        }

        $contentCheck = self::checkContentRequirements( $http );
        if ( $contentCheck !== false )
        {
            extract( $contentCheck );

            // Check to see if commenting is turned on, on the object level
            $commentContent = ezcomPostHelper::checkCommentPermission( $contentObject, $languageCode, $foundCommentAttribute );
            if ( $commentContent['show_comments'] && $commentContent['enable_comment'] )
            {
                $currentTime = time();
                $currentUser = eZUser::currentUser();

                $commentObject = ezcomComment::create();
                $formTool->fillObject( $commentObject );

                $commentObject->setAttribute( 'contentobject_id', $contentObjectId );
                $commentObject->setAttribute( 'language_id', eZContentLanguage::idByLocale( $languageCode ) );
                $commentObject->setAttribute( 'session_key', $http->sessionID() );
                $commentObject->setAttribute( 'ip', ezcomUtility::instance()->getUserIP() );
                $commentObject->setAttribute( 'user_id', $currentUser->attribute( 'contentobject_id' ) );
                $commentObject->setAttribute( 'created', $currentTime );
                $commentObject->setAttribute( 'modified', $currentTime );

                $notification = $formTool->fieldValue( 'notificationField' );
                $email = $commentObject->attribute( 'email' );
                $changeNotification = false;
                if ( $notification === true )
                {
                    // email is enabled in setting
                    if ( !is_null( $email ) )
                    {
                        $changeNotification = true;
                    }
                    else
                    {
                        //email is disabled in setting but user logged in
                        if ( is_null( $email ) && !$currentUser->isAnonymous() )
                        {
                            $changeNotification = true;
                            $email = $currentUser->attribute( 'email' );
                            $commentObject->setAttribute( 'email', $email );
                        }
                    }
                }

                $commentManager = ezcomCommentManager::instance();

                $existingNotification = false;
                if ( $changeNotification )
                {
                    $existingNotification = ezcomSubscription::exists( $contentObjectId, eZContentLanguage::idByLocale( $languageCode ), 'ezcomcomment', $email );
                    if ( !$existingNotification )
                    {
                        $result = $commentManager->addComment( $commentObject, $currentUser, null, true );
                    }
                    else
                    {
                        $result = $commentManager->addComment( $commentObject, $currentUser );
                    }
                }
                else
                {
                    $result = $commentManager->addComment( $commentObject, $currentUser );
                }

                if ( $result === true )
                {
                    $tpl = eZTemplate::factory();
                    $tpl->setVariable( 'contentobject', $contentObject );
                    $tpl->setVariable( 'language_code', $languageCode );
                    $tpl->setVariable( 'comment', $commentObject );
                    $tpl->setVariable( 'node', $contentObject->mainNode() );
                    $tpl->setVariable( 'current_user', $currentUser );

                    if ( $currentUser->isAnonymous() )
                    {
                        $cookieManager = ezcomCookieManager::instance();
                        if ( $remembermeChecked )
                        {
                            $cookieManager->storeCookie( $commentObject );
                        }
                        else
                        {
                            $cookieManager->clearCookie();
                        }
                    }

                    $successMessage = ezpI18n::tr( 'ngcomments/comment/ezjsc', 'Your comment has been added!' );
                    if ( $changeNotification )
                    {
                        if ( !$currentUser->isAnonymous() )
                        {
                            if ( $existingNotification )
                            {
                                $successMessage = ezpI18n::tr( 'ezcomments/comment/add', 'You have already subscribed to comment updates on this content.' );
                            }
                            else
                            {
                                $successMessage = ezpI18n::tr( 'ezcomments/comment/add', 'You will receive comment updates on the content.' );
                            }
                        }
                        else
                        {
                            $successMessage = ezpI18n::tr( 'ezcomments/comment/add', 'A confirmation email has been sent to your email address. You will receive comment updates after confirmation.' );
                        }
                    }

                    return array( 'status' => 'success',
                                  'content' => $tpl->fetch( 'design:comment/view/comment_item.tpl' ),
                                  'message' => $successMessage );
                }
            }
        }

        return array( 'status' => 'error',
                      'message' => ezpI18n::tr( 'ngcomments/comment/ezjsc', 'Error adding comment!' ) );
    }

    public static function editCommentLoad( $args )
    {
        $http = eZHTTPTool::instance();
        $ezCommentsINI = eZINI::instance( 'ezcomments.ini' );

        if ( $http->hasPostVariable( 'CommentID' ) )
        {
            $commentID = (int) $http->postVariable( 'CommentID' );

            if ( $commentID > 0 )
            {
                $commentObject = ezcomComment::fetch( $commentID );
                if ( $commentObject instanceof ezcomComment )
                {
                    $tpl = eZTemplate::factory();
                    $tpl->setVariable( 'comment', $commentObject );

                    $availableFields = $ezCommentsINI->variable( 'FormSettings', 'AvailableFields' );
                    $notificationEnabled = in_array( 'notificationField', $availableFields );
                    $notified = null;

                    if( $notificationEnabled )
                    {
                        $notified = ezcomSubscription::exists( $commentObject->attribute( 'contentobject_id' ),
                                                               $commentObject->attribute( 'language_id' ),
                                                               'ezcomcomment',
                                                               $commentObject->attribute( 'email' ) );
                        $tpl->setVariable( 'notified', $notified );
                    }

                    return array( 'status' => 'success',
                                  'content' => $tpl->fetch( 'design:comment/edit_comment.tpl' ) );
                }
            }
        }

        return array( 'status' => 'error',
                      'message' => ezpI18n::tr( 'ngcomments/comment/ezjsc', 'Error loading comment edit interface!' ) );
    }

    public static function editComment( $args )
    {
        $http = eZHTTPTool::instance();
        $ezCommentsINI = eZINI::instance( 'ezcomments.ini' );

        $formTool = ezcomEditCommentTool::instance();
        $formStatus = $formTool->checkVars();
        if ( !$formStatus )
        {
            return array( 'status' => 'error',
                          'message' => implode( "\n", array_values( $formTool->messages() ) ) );
        }

        if ( $http->hasPostVariable( 'CommentID' ) )
        {
            $commentID = (int) $http->postVariable( 'CommentID' );

            if ( $commentID > 0 )
            {
                $commentObject = ezcomComment::fetch( $commentID );
                if ( $commentObject instanceof ezcomComment )
                {
                    $contentObject = $commentObject->contentObject();
                    $contentNode = $contentObject->mainNode();
                    $contentObjectID = $commentObject->attribute( 'contentobject_id' );
                    $languageID = $commentObject->attribute( 'language_id' );
                    $languageCode = eZContentLanguage::fetch( $languageID )->attribute( 'locale' );
                    $canEditResult = ezcomPermission::hasAccessToFunction( 'edit', $contentObject, $languageCode, $commentObject, null, $contentNode );

                    if ( $canEditResult['result'] )
                    {
                        $currentTime = time();
                        $currentUser = eZUser::currentUser();

                        $formSettings = $ezCommentsINI->variable( 'FormSettings', 'AvailableFields' );
                        $notificationEnabled = in_array( 'notificationField', $formSettings );
                        $emailEnabled = in_array( 'email', $formSettings );
                        $notified = null;
                        if ( $notificationEnabled )
                        {
                            $notified = ezcomSubscription::exists( $contentObjectID, $languageID, 'ezcomcomment', $commentObject->attribute( 'email' ) );
                        }

                        $formTool->fillObject( $commentObject );
                        $commentObject->setAttribute( 'modified', $currentTime );

                        // update comments
                        $commentManager = ezcomCommentManager::instance();
                        $clientNotified = $formTool->fieldValue( 'notificationField' );
                        $updateResult = null;
                        // if notified and clientNotified are not null and different, change notification
                        if ( $notificationEnabled && $emailEnabled && $commentObject->attribute( 'email' ) != '' && $notified != $clientNotified )
                        {
                            $updateResult = $commentManager->updateComment( $commentObject, null, $currentTime, $clientNotified );
                        }
                        else
                        {
                            $updateResult = $commentManager->updateComment( $commentObject, null, $currentTime );
                        }

                        if($updateResult === true)
                        {
                            $tpl = eZTemplate::factory();
                            $tpl->setVariable( 'contentobject', $contentObject );
                            $tpl->setVariable( 'language_code', $languageCode );
                            $tpl->setVariable( 'comment', $commentObject );
                            $tpl->setVariable( 'node', $contentNode );
                            $tpl->setVariable( 'current_user', $currentUser );

                            return array( 'status' => 'success',
                                          'content' => $tpl->fetch( 'design:comment/view/comment_item.tpl' ),
                                          'message' => ezpI18n::tr( 'ngcomments/comment/ezjsc', 'Comment was successfully edited!' ) );
                        }
                    }
                }
            }
        }

        return array( 'status' => 'error',
                      'message' => ezpI18n::tr( 'ngcomments/comment/ezjsc', 'Error editing the comment!' ) );
    }

    public static function deleteComment( $args )
    {
        $http = eZHTTPTool::instance();

        if ( $http->hasPostVariable( 'CommentID' ) )
        {
            $commentID = (int) trim( $http->postVariable( 'CommentID' ) );

            if ( $commentID > 0 )
            {
                $comment = ezcomComment::fetch( $commentID );
                if ( $comment instanceof ezcomComment )
                {
                    $contentObject = $comment->contentObject();
                    $contentNode = $contentObject->mainNode();
                    $languageID = $comment->attribute( 'language_id' );
                    $languageCode = eZContentLanguage::fetch( $languageID )->attribute( 'locale' );
                    $canDeleteResult = ezcomPermission::hasAccessToFunction( 'delete', $contentObject, $languageCode, $comment, null, $contentNode );

                    $dataMap = $contentObject->fetchDataMap( false, $languageCode );
                    $commentAttribute = false;
                    foreach ( $dataMap as $attr )
                    {
                        if ( $attr->attribute( 'data_type_string' ) === 'ezcomcomments' )
                        {
                            $commentAttribute = $attr;
                            break;
                        }
                    }

                    if ( $commentAttribute instanceof eZContentObjectAttribute )
                    {
                        $commentAttributeContent = $commentAttribute->content();
                        if ( $canDeleteResult['result'] && $commentAttributeContent['show_comments'] )
                        {
                            $commentManager = ezcomCommentManager::instance();
                            $deleteResult = $commentManager->deleteComment( $comment );
                            if ( $deleteResult === true )
                            {
                                return array( 'status' => 'success',
                                              'message' => ezpI18n::tr( 'ngcomments/comment/ezjsc', 'Comment was successfully deleted!' ) );
                            }
                        }
                    }
                }
            }
        }

        return array( 'status' => 'error',
                      'message' => ezpI18n::tr( 'ngcomments/comment/ezjsc', 'Error deleting the comment!' ) );
    }

    public static function checkContentRequirements( $http )
    {
        // Check that the object params are 'ok'
        if ( !$http->hasPostVariable( 'ContentObjectID' ) )
        {
            return false;
        }
        $contentObjectId = (int) $http->postVariable( 'ContentObjectID' );

        // Either use provided language code, or fallback on siteaccess default
        if ( $http->hasPostVariable( 'CommentLanguageCode' ) )
        {
            $languageCode = $http->postVariable( 'CommentLanguageCode' );
            $language = eZContentLanguage::fetchByLocale( $languageCode );
            if ( $language === false )
            {
                return false;
            }
        }
        else
        {
            $defaultLanguage = eZContentLanguage::topPriorityLanguage();
            $languageCode = $defaultLanguage->attribute( 'locale' );
        }

        // Check that our object is actually a valid holder of comments
        $contentObject = eZContentObject::fetch( $contentObjectId );
        if ( !( $contentObject instanceof eZContentObject ) )
        {
            return false;
        }

        $dataMap = $contentObject->fetchDataMap( false, $languageCode );
        $foundCommentAttribute = false;
        foreach ( $dataMap as $attr )
        {
            if ( $attr->attribute( 'data_type_string' ) === 'ezcomcomments' )
            {
                $foundCommentAttribute = $attr;
                break;
            }
        }

        // if there is no ezcomcomments attribute inside the content, return
        if ( !$foundCommentAttribute )
        {
            return false;
        }

        return compact( 'contentObjectId', 'languageCode', 'contentObject', 'foundCommentAttribute' );
    }
}

?>
