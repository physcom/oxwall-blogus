<?php


class BLOGUS_BOL_PostService
{
    const FEED_ENTITY_TYPE = 'blog-post';
    const PRIVACY_ACTION_VIEW_BLOG_POSTS = 'blogs_view_blog_posts';
    const PRIVACY_ACTION_COMMENT_BLOG_POSTS = 'blogs_comment_blog_posts';

    const POST_STATUS_PUBLISHED = 0;
    const POST_STATUS_DRAFT = 1;
    const POST_STATUS_DRAFT_WAS_NOT_PUBLISHED = 2;
    const POST_STATUS_APPROVAL = 3;

    const EVENT_AFTER_DELETE = 'blogs.after_delete';
    const EVENT_BEFORE_DELETE = 'blogs.before_delete';
    const EVENT_AFTER_EDIT = 'blogs.after_edit';
    const EVENT_AFTER_ADD = 'blogs.after_add';

    /*
     * @var BLOGUS_BOL_PostService
     */
    private static $classInstance;

    /**
     * @var array
     */
    private $config = array();

    /*
      @var BLOGUS_BOL_PostDao
     */
    private $dao;

    private function __construct()
    {
        $this->dao = BLOGUS_BOL_PostDao::getInstance();

        $this->config['allowedMPElements'] = array();
    }

    public function getConfig()
    {
        return $this->config;
    }

        /**
     * Returns class instance
     *
     * @return BLOGUS_BOL_PostService
     */
    public static function getInstance()
    {
        if ( !isset(self::$classInstance) )
            self::$classInstance = new self();

        return self::$classInstance;
    }

    public function save( $dto )
    {
        $dao = $this->dao;

        return $dao->save($dto);
    }

    /**
     * @return BLOGUS_BOL_Post
     */
    public function findById( $id )
    {
        $dao = $this->dao;

        return $dao->findById($id);
    }

   
    /*
     * $which can take on of two following 'next', 'prev' values
     */

    public function findAdjacentUserPost( $id, $postId, $which )
    {
        return $this->dao->findAdjacentUserPost($id, $postId, $which);
    }

    public function findUserPostList( $userId, $first, $count )
    {
        return $this->dao->findUserPostList($userId, $first, $count);
    }

    public function findUserDraftList( $userId, $first, $count )
    {
        return $this->dao->findUserDraftList($userId, $first, $count);
    }

    public function countUserPost( $userId )
    {
        return $this->dao->countUserPost($userId);
    }

    public function countUserPostComment( $userId )
    {
        return $this->dao->countUserPostComment($userId);
    }

    public function countUserDraft( $userId )
    {
        return $this->dao->countUserDraft($userId);
    }

    public function findUserPostCommentList( $userId, $first, $count )
    {
        return $this->dao->findUserPostCommentList($userId, $first, $count);
    }

    public function findUserLastPost( $userId )
    {
        return $this->dao->findUserLastPost($userId);
    }

    public function findUserArchiveData( $id )
    {
        return $this->dao->findUserArchiveData($id);
    }

    public function findUserPostListByPeriod( $id, $lb, $ub, $first, $count )
    {
        return $this->dao->findUserPostListByPeriod($id, $lb, $ub, $first, $count);
    }

    public function countUserPostByPeriod( $id, $lb, $ub )
    {
        return $this->dao->countUserPostByPeriod($id, $lb, $ub);
    }

    /**
     * Find latest public list ids
     *
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findLatestPublicListIds( $first, $count )
    {
        return $this->dao->findLatestPublicListIds($first, $count);
    }

    //</USER-BLOG>
    //<SITE-BLOG>
    public function findList( $first, $count )
    {
        return $this->dao->findList($first, $count);
    }

    public function countAll()
    {
        return $this->dao->countAll();
    }

    public function countPosts()
    {
        return $this->dao->countPosts();
    }

    public function findTopRatedList( $first, $count )
    {
        return $this->dao->findTopRatedList($first, $count);
    }

    public function findListByTag( $tag, $first, $count )
    {
        return $this->dao->findListByTag($tag, $first, $count);
    }

    public function countByTag( $tag )
    {
        return $this->dao->countByTag($tag);
    }

    public function delete( BLOGUS_BOL_Post $dto )
    {
        $this->deletePost($dto->getId());
    }

    //</SITE-BLOG>

    public function findListByIdList( $list )
    {
        return $this->dao->findListByIdList($list);
    }

    public function onAuthorSuspend( OW_Event $event )
    {
        $params = $event->getParams();
    }

    /**
     * Get set of allowed tags for blogs
     *
     * @return array
     */
    public function getAllowedHtmlTags()
    {
        return array("object", "embed", "param", "strong", "i", "u", "a", "!--more--", "img", "blockquote", "span", "pre", "iframe");
    }

    /**
     * Find latest posts authors ids
     *
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findLatestPublicPostsAuthorsIds($first, $count)
    {
        return $this->dao->findLatestPublicPostsAuthorsIds($first, $count);
    }

    public function updateBlogsPrivacy( $userId, $privacy )
    {
        $count = $this->countUserPost($userId);
        $entities = BLOGUS_BOL_PostService::getInstance()->findUserPostList($userId, 0, $count);
        $entityIds = array();

        foreach ($entities as $post)
        {
            $entityIds[] = $post->getId();
        }

        $status = ( $privacy == 'everybody' ) ? true : false;

        $event = new OW_Event('base.update_entity_items_status', array(
            'entityType' => 'blog-post',
            'entityIds' => $entityIds,
            'status' => $status,
        ));
        OW::getEventManager()->trigger($event);

        $this->dao->updateBlogsPrivacy( $userId, $privacy );
        OW::getCacheManager()->clean( array( BLOGUS_BOL_Post::CACHE_TAG_POST_COUNT ));
    }

    public function processPostText($text)
    {
        $text = str_replace('&nbsp;', ' ', $text);
        $text = strip_tags($text);
        return $text;
    }

    public function findUserNewCommentCount($userId)
    {
        return $this->dao->countUserPostNewComment($userId);
    }


    public function findPostListByIds($postIds)
    {
        return $this->dao->findByIdList($postIds);
    }

    public function getPostUrl($post)
    {
        return OW::getRouter()->urlForRoute('post', array('id'=>$post->getId()));
    }
}