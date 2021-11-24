<?php

class BLOGUS_BOL_PostDao extends OW_BaseDao
{
    const CACHE_TAG_POST_COUNT = 'blogs.post_count';
    const CACHE_LIFE_TIME = 86400; //24 hour

    /**
     * Class constructor
     *
     */
    protected function __construct()
    {
        parent::__construct();
    }
    /**
     * Class instance
     *
     * @var BLOGUS_BOL_PostDao
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return BLOGUS_BOL_PostDao
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'BLOGUS_BOL_Post';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'blogus_post';
    }

    public function findAdjacentUserPost( $id, $postId, $which )
    {
        $part = array();

        switch ( $which )
        {
            case 'next':
                $part['projection'] = 'MIN(`id`)';
                $part['inequality'] = '>';
                break;

            case 'prev':
                $part['projection'] = 'MAX(`id`)';
                $part['inequality'] = '<';
                break;
        }

        $query = "
			SELECT {$part['projection']}
			FROM {$this->getTableName()}
			WHERE isDraft = 0 AND authorId = ? AND id {$part['inequality']} ?
		";

        $id = $this->dbo->queryForColumn($query, array($id, $postId));

        return (!empty($id)) ? $this->findById($id) : null;
    }

    public function deleteByAuthorId( $userId )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $userId);

        $this->deleteByExample($ex);
    }

    public function findUserPostList( $userId, $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $userId)
            ->setOrder('`timestamp` DESC')
            ->andFieldEqual('isDraft', 0)
            ->setLimitClause($first, $count);

        $cacheLifeTime = self::CACHE_LIFE_TIME;
        $tags = array( self::CACHE_TAG_POST_COUNT );

        return $this->findListByExample($ex, $cacheLifeTime, $tags);
    }

    public function findUserDraftList( $userId, $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $userId)
            ->andFieldNotEqual('isDraft', 0)
            ->setOrder('`timestamp` DESC')
            ->setLimitClause($first, $count);

        $cacheLifeTime = self::CACHE_LIFE_TIME;
        $tags = array( self::CACHE_TAG_POST_COUNT );

        return $this->findListByExample($ex, $cacheLifeTime, $tags);
    }

    public function countUserPost( $userId )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $userId);
        $ex->andFieldEqual('isDraft', 0);

        $cacheLifeTime = self::CACHE_LIFE_TIME;
        $tags = array( self::CACHE_TAG_POST_COUNT );

        return $this->countByExample($ex,$cacheLifeTime, $tags);
    }

    public function countUserDraft( $userId )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $userId);
        $ex->andFieldNotEqual('isDraft', 0);
        $ex->andFieldNotEqual('isDraft', 3);

        $cacheLifeTime = self::CACHE_LIFE_TIME;
        $tags = array( self::CACHE_TAG_POST_COUNT );

        return $this->countByExample($ex, $cacheLifeTime, $tags);
    }

    public function countPosts()
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('isDraft', 0);
        $ex->andFieldEqual('privacy', 'everybody');

        $cacheLifeTime = self::CACHE_LIFE_TIME;
        $tags = array( self::CACHE_TAG_POST_COUNT );

        return $this->countByExample($ex, $cacheLifeTime, $tags);
    }

    public function countUserPostComment( $userId )
    {
        $query = "
		SELECT COUNT(*)
		FROM `{$this->getTableName()}` as `p`
		INNER JOIN `" . BOL_CommentEntityDao::getInstance()->getTableName() . "` as `ce`
			ON( `p`.`id` = `ce`.`entityId` and `entityType` = 'blog-post' )
		INNER JOIN `" . BOL_CommentDao::getInstance()->getTableName() . "` as `c`
			ON( `ce`.`id` = `c`.`commentEntityId` )

		WHERE `p`.`authorId` = ? AND `p`.`isDraft` = 0
		";

        return $this->dbo->queryForColumn($query, array($userId));
    }

    public function countUserPostNewComment( $userId )
    {
        $query = "
		SELECT COUNT(*)
		FROM `{$this->getTableName()}` as `p`
		INNER JOIN `" . BOL_CommentEntityDao::getInstance()->getTableName() . "` as `ce`
			ON( `p`.`id` = `ce`.`entityId` and `entityType` = 'blog-post' )
		INNER JOIN `" . BOL_CommentDao::getInstance()->getTableName() . "` as `c`
			ON( `ce`.`id` = `c`.`commentEntityId` )

		WHERE `p`.`authorId` = ? AND `p`.`isDraft` = 0 AND `c`.`createStamp` > ".(time()-86400*7)."
		";

        return $this->dbo->queryForColumn($query, array($userId));
    }

    public function findUserPostCommentList( $userId, $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $query = "
		SELECT `c`.*, `ce`.`entityId`
		FROM `{$this->getTableName()}` as `p`
		INNER JOIN `" . BOL_CommentEntityDao::getInstance()->getTableName() . "` as `ce`
			ON( `p`.`id` = `ce`.`entityId` and `entityType` = 'blog-post' )
		INNER JOIN `" . BOL_CommentDao::getInstance()->getTableName() . "` as `c`
			ON( `ce`.`id` = `c`.`commentEntityId` )

		WHERE `p`.`authorId` = ? AND `p`.`isDraft` = 0
		ORDER BY `c`.`createStamp` DESC
		LIMIT ?, ?
		";

        return $this->dbo->queryForList($query, array($userId, $first, $count));
    }

    public function findUserLastPost( $userId )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $userId)->andFieldEqual('isDraft', 0)->setOrder('timestamp DESC')->setLimitClause(0, 1);

        return $this->findObjectByExample($ex);
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
        $query = "SELECT
            `authorId`
        FROM
            `" . $this->getTableName() . "`
        WHERE
            `privacy` = :privacy
                AND
            `isDraft` = :draft
        GROUP BY
            `authorId`
        ORDER BY
            `timestamp` DESC
        LIMIT :f, :c";

        return $this->dbo->queryForColumnList($query, array(
            'privacy' => 'everybody',
            'draft' => 0,
            'f' => (int) $first,
            'c' => (int) $count,
        ));
    }

    public function findUserArchiveData( $id )
    {
        $query = "
			SELECT YEAR( FROM_UNIXTIME(`timestamp`) ) as `y`, MONTH( FROM_UNIXTIME(`timestamp`) ) as `m`
			FROM `{$this->getTableName()}`
			WHERE isDraft = 0 AND `authorId` = ?
			GROUP BY `y`, `m`
		";

        return $this->dbo->queryForList($query, array($id));
    }

    public function findUserPostListByPeriod( $id, $lb, $ub, $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $id);

        $ex->andFieldBetween('timestamp', $lb, $ub);
        $ex->andFieldEqual('isDraft', 0);
        $ex->setOrder('`timestamp` DESC');
        $ex->setLimitClause($first, $count);

        return $this->findListByExample($ex);
    }

    public function countUserPostByPeriod( $id, $lb, $ub )
    {
        $ex = new OW_Example();
        $ex->andFieldEqual('authorId', $id);
        $ex->andFieldBetween('timestamp', $lb, $ub);
        $ex->andFieldEqual('isDraft', 0);
        $ex->setOrder('`timestamp` DESC');

        return $this->countByExample($ex);
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
        $ex = new OW_Example();
        $ex->andFieldEqual('isDraft', 0);
        $ex->andFieldEqual('privacy', 'everybody');
        $ex->setOrder('timestamp desc')->setLimitClause((int) $first, (int) $count);

        return $this->findIdListByExample($ex);
    }

    public function findList( $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $ex = new OW_Example();
        $ex->andFieldEqual('isDraft', 0);
        $ex->andFieldEqual('privacy', 'everybody');
        $ex->setOrder('timestamp desc')->setLimitClause((int) $first, (int) $count);

        $cacheLifeTime = self::CACHE_LIFE_TIME;
        $tags = array( self::CACHE_TAG_POST_COUNT );

        return $this->findListByExample($ex, $cacheLifeTime, $tags);
    }

    public function findTopRatedList( $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $query = "
			SELECT p.*, IF(SUM(r.score) IS NOT NULL, SUM(r.score), 0) as `t`
			FROM `{$this->getTableName()}` as p
			LEFT JOIN `ow_base_rate` as r /*todo: 8aa*/
			ON( r.`entityType` = 'blog-post' AND p.id = r.`entityId` )
			WHERE p.isDraft = 0
			GROUP BY p.`id`
			ORDER BY `t` DESC
			LIMIT ?, ?";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function findListByTag( $tag, $first, $count )
    {
        if ($first < 0)
        {
            $first = 0;
        }

        if ($count < 0)
        {
            $count = 1;
        }

        $query = "
			SELECT p.*
			FROM `ow_base_tag` as t
			INNER JOIN `ow_base_entity_tag` as `et`
				ON(`t`.`id` = `et`.`tagId` AND `et`.`entityType` = 'blog-post')
			INNER JOIN `{$this->getTableName()}` as p
				ON(`et`.`entityId` = `p`.`id`)
			WHERE p.isDraft = 0 AND `t`.`label` = '{$tag}'
			ORDER BY
			LIMIT ?, ?";

        return $this->dbo->queryForObjectList($query, $this->getDtoClassName(), array($first, $count));
    }

    public function countByTag( $tag )
    {
        $query = "
			SELECT count( * )
			FROM `ow_base_tag` as t
			INNER JOIN `ow_base_entity_tag` as `et`
				ON(`t`.`id` = `et`.`tagId` AND `et`.`entityType` = 'blog-post')
			INNER JOIN `{$this->getTableName()}` as p
				ON(`et`.`entityId` = `p`.`id`)
			WHERE p.isDraft = 0 AND `t`.`label` = '{$tag}'";

        return $this->dbo->queryForColumn($query);
    }

    public function findListByIdList( $list )
    {
        $ex = new OW_Example();

        $ex->andFieldInArray('id', $list);
        $ex->andFieldEqual('privacy', 'everybody');

        $ex->setOrder('timestamp DESC');

        return $this->findListByExample($ex);
    }

    public function updateBlogsPrivacy( $authorId, $privacy )
    {
        $this->clearCache();

        $sql = "UPDATE `" . $this->getTableName() . "` SET `privacy` = :privacy
            WHERE `authorId` = :authorId";

        $this->dbo->query($sql, array('privacy' => $privacy, 'authorId' => $authorId));
    }

    public function clearCache()
    {
        OW::getCacheManager()->clean( array( PostDao::CACHE_TAG_POST_COUNT ));
    }
}
