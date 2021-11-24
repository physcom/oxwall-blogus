<?php


class BLOGUS_CTRL_Save extends OW_ActionController
{

    public function index( $params = array() )
    {

        
        if (OW::getRequest()->isAjax())
        {
            exit();
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthenticateException();
        }
        
          

        $this->setPageHeading(OW::getLanguage()->text('blogs', 'save_page_heading'));
        $this->setPageHeadingIconClass('ow_ic_write');

        if ( !OW::getUser()->isAuthorized('blogs', 'add') )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('blogs', 'add_blog');
            throw new AuthorizationException($status['msg']);

            return;
        }
        
        $this->assign('authMsg', null);

        $id = empty($params['id']) ? 0 : $params['id'];
          
        $service = BLOGUS_BOL_PostService::getInstance(); /* @var $service BLOGUS_BOL_PostService */

        
        if ( intval($id) > 0 )
        {
            $post = $service->findById($id);

            if ($post->authorId != OW::getUser()->getId() && !OW::getUser()->isAuthorized('blogs'))
            {
                throw new Redirect404Exception();
            }

            $eventParams = array(
                'action' => BLOGUS_BOL_PostService::PRIVACY_ACTION_VIEW_BLOG_POSTS,
                'ownerId' => $post->authorId
            );

            $privacy = OW::getEventManager()->getInstance()->call('plugin.privacy.get_privacy', $eventParams);
            if (!empty($privacy))
            {
                $post->setPrivacy($privacy);
            }

        }
        else
        {
            $post = new BLOGUS_BOL_Post();

            $eventParams = array(
                'action' => BLOGUS_BOL_PostService::PRIVACY_ACTION_VIEW_BLOG_POSTS,
                'ownerId' => OW::getUser()->getId()
            );

            $privacy = OW::getEventManager()->getInstance()->call('plugin.privacy.get_privacy', $eventParams);
            if (!empty($privacy))
            {
                $post->setPrivacy($privacy);
            }

            $post->setAuthorId(OW::getUser()->getId());
        }

        $form = new SaveForm($post);

        if ( OW::getRequest()->isPost() && (!empty($_POST['command']) && in_array($_POST['command'], array('draft', 'publish')) ) && $form->isValid($_POST) )
        {
            $form->process($this);
            OW::getApplication()->redirect(OW::getRouter()->urlForRoute('blogus.save.edit', array('id' => $post->getId())));
        }

        $this->addForm($form);

        $this->assign('info', array('dto' => $post));

        OW::getDocument()->setTitle(OW::getLanguage()->text('blogs', 'meta_title_new_blog_post'));
        OW::getDocument()->setDescription(OW::getLanguage()->text('blogs', 'meta_description_new_blog_post'));

    }

    public function delete( $params )
    {
        if (OW::getRequest()->isAjax() || !OW::getUser()->isAuthenticated())
        {
            exit();
        }
        /*
          @var $service BLOGUS_BOL_PostService
         */
        $service = BLOGUS_BOL_PostService::getInstance();

        $id = $params['id'];

        $dto = $service->findById($id);

        if ( !empty($dto) )
        {
            if ($dto->authorId == OW::getUser()->getId() || OW::getUser()->isAuthorized('blogs'))
            {
                OW::getEventManager()->trigger(new OW_Event(BLOGUS_BOL_PostService::EVENT_BEFORE_DELETE, array(
                    'postId' => $id
                )));
                $service->delete($dto);
                OW::getEventManager()->trigger(new OW_Event(PostService::EVENT_AFTER_DELETE, array(
                    'postId' => $id
                )));
            }
        }

        if ( !empty($_GET['back-to']) )
        {
            $this->redirect($_GET['back-to']);
        }

        $author = BOL_UserService::getInstance()->findUserById($dto->authorId);

        $this->redirect(OW::getRouter()->urlForRoute('user-blog', array('user' => $author->getUsername())));
    }
}

class SaveForm extends Form
{
    /**
     *
     * @var BLOGUS_BOL_Post
     */
    private $post;
    /**
     *
     * @var type BLOGUS_BOL_PostService
     */
    private $service;


    public function __construct( BLOGUS_BOL_Post $post, $tags = array() )
    {
        parent::__construct('save');

        $this->service = BLOGUS_BOL_PostService::getInstance();

        $this->post = $post;

        $this->setMethod('post');

        $titleTextField = new TextField('title');

        $phoneNumberField = new TextField('phoneNumber');

        $this->addElement($titleTextField->setLabel(OW::getLanguage()->text('blogs', 'save_form_lbl_title'))->setValue($post->getTitle())->setRequired(true));

        $this->addElement($phoneNumberField->setLabel(OW::getLanguage()->text('blogs', 'save_form_lbl_phone'))->setValue($post->getPhoneNumber())->setRequired(true));

        $buttons = array(
            BOL_TextFormatService::WS_BTN_BOLD,
            BOL_TextFormatService::WS_BTN_ITALIC,
            BOL_TextFormatService::WS_BTN_UNDERLINE,
            BOL_TextFormatService::WS_BTN_IMAGE,
            BOL_TextFormatService::WS_BTN_LINK,
            BOL_TextFormatService::WS_BTN_ORDERED_LIST,
            BOL_TextFormatService::WS_BTN_UNORDERED_LIST,
            BOL_TextFormatService::WS_BTN_MORE,
            BOL_TextFormatService::WS_BTN_SWITCH_HTML,
            BOL_TextFormatService::WS_BTN_HTML,
            BOL_TextFormatService::WS_BTN_VIDEO
        );

        $postTextArea = new WysiwygTextarea('post', $buttons);
        $postTextArea->setSize(WysiwygTextarea::SIZE_L);
        $postTextArea->setLabel(OW::getLanguage()->text('blogs', 'save_form_lbl_post'));
        $postTextArea->setValue($post->getPost());
        $postTextArea->setRequired(true);
        $this->addElement($postTextArea);

        $draftSubmit = new Submit('draft');
        $draftSubmit->addAttribute('onclick', "$('#save_post_command').attr('value', 'draft');");

        if ( $post->getId() != null && !$post->isDraft() )
        {
            $text = OW::getLanguage()->text('blogs', 'change_status_draft');
        }
        else
        {
            $text = OW::getLanguage()->text('blogs', 'sava_draft');
        }

        $this->addElement($draftSubmit->setValue($text));

        if ( $post->getId() != null && !$post->isDraft() )
        {
            $text = OW::getLanguage()->text('blogs', 'update');
        }
        else
        {
            $text = OW::getLanguage()->text('blogs', 'save_publish');
        }

        $publishSubmit = new Submit('publish');
        $publishSubmit->addAttribute('onclick', "$('#save_post_command').attr('value', 'publish');");

        $this->addElement($publishSubmit->setValue($text));

        $tagService = BOL_TagService::getInstance();

        $tags = array();

        if ( intval($this->post->getId()) > 0 )
        {
            $arr = $tagService->findEntityTags($this->post->getId(), 'blog-post');

            foreach ( (!empty($arr) ? $arr : array() ) as $dto )
            {
                $tags[] = $dto->getLabel();
            }
        }

        $tf = new TagsInputField('tf');
        $tf->setLabel(OW::getLanguage()->text('blogs', 'tags_field_label'));
        $tf->setValue($tags);

        $this->addElement($tf);
    }

    public function process( $ctrl )
    {
        OW::getCacheManager()->clean( array( BLOGUS_BOL_PostDao::CACHE_TAG_POST_COUNT ));

        $service = BLOGUS_BOL_PostService::getInstance(); /* @var $postDao BLOGUS_BOL_PostService */

        $data = $this->getValues();

        $data['title'] = UTIL_HtmlTag::stripJs($data['title']);

        $data['phoneNumber'] = UTIL_HtmlTag::sanitize($data['phoneNumber']);


        $postIsNotPublished = $this->post->getStatus() == 2;

        $text = UTIL_HtmlTag::sanitize($data['post']);

        /* @var $post BLOGUS_BOL_Post */
        $this->post->setTitle($data['title']);
        $this->post->setPhoneNumber($data['phoneNumber']);
        $this->post->setPost($text);
        $this->post->setIsDraft($_POST['command'] == 'draft');

        $isCreate = empty($this->post->id);
        if ( $isCreate )
        {
            $this->post->setTimestamp(time());
            //Required to make #698 and #822 work together
            if ( $_POST['command'] == 'draft' )
            {
                $this->post->setIsDraft(2);
            }

        }
        else
        {
            //If post is not new and saved as draft, remove their item from newsfeed
            if ( $_POST['command'] == 'draft' )
            {
                OW::getEventManager()->trigger(new OW_Event('feed.delete_item', array('entityType' => 'blog-post', 'entityId' => $this->post->id)));
            }
            else if($postIsNotPublished)
            {
                // Update timestamp if post was published for the first time
                $this->post->setTimestamp(time());
            }

        }

        $service->save($this->post);

        $tags = array();
        if ( intval($this->post->getId()) > 0 )
        {
            $tags = $data['tf'];
            foreach ( $tags as $id => $tag )
            {
                $tags[$id] = UTIL_HtmlTag::stripTags($tag);
            }
        }
        $tagService = BOL_TagService::getInstance();
        $tagService->updateEntityTags($this->post->getId(), 'blog-post', $tags );

        if ( $this->post->isDraft() )
        {
            $tagService->setEntityStatus('blog-post', $this->post->getId(), false);

            if ( $isCreate )
            {
                OW::getFeedback()->info(OW::getLanguage()->text('blogs', 'create_draft_success_msg'));
            }
            else
            {
                OW::getFeedback()->info(OW::getLanguage()->text('blogs', 'edit_draft_success_msg'));
            }
        }
        else
        {
            $tagService->setEntityStatus('blog-post', $this->post->getId(), true);

            //Newsfeed
            $event = new OW_Event('feed.action', array(
                'pluginKey' => 'blogs',
                'entityType' => 'blog-post',
                'entityId' => $this->post->getId(),
                'userId' => $this->post->getAuthorId(),
            ));
            OW::getEventManager()->trigger($event);

            if ( $isCreate )
            {
                OW::getFeedback()->info(OW::getLanguage()->text('blogs', 'create_success_msg'));

                OW::getEventManager()->trigger(new OW_Event(BLOGUS_BOL_PostService::EVENT_AFTER_ADD, array(
                    'postId' => $this->post->getId()
                )));
            }
            else
            {
                OW::getFeedback()->info(OW::getLanguage()->text('blogs', 'edit_success_msg'));
                OW::getEventManager()->trigger(new OW_Event(BLOGUS_BOL_PostService::EVENT_AFTER_EDIT, array(
                    'postId' => $this->post->getId()
                )));
            }

            $blog_post = BLOGUS_BOL_PostService::getInstance()->findById($this->post->id);

            if( $blog_post->isDraft == BLOGUS_BOL_PostService::POST_STATUS_PUBLISHED )
            {
                BOL_AuthorizationService::getInstance()->trackActionForUser($blog_post->authorId, 'blogs', 'add_blog');
            }

            $ctrl->redirect(OW::getRouter()->urlForRoute('blogus.post', array('id' => $this->post->getId())));
        }
    }
}

?>
