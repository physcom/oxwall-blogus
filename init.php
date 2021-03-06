<?php

$plugin = OW::getPluginManager()->getPlugin('blogus');

OW::getAutoloader()->addClass('BLOGUS_BOL_Post', $plugin->getBolDir() . DS . 'post.php');
OW::getAutoloader()->addClass('BLOGUS_BOL_PostDao', $plugin->getBolDir() . DS . 'post_dao.php');
OW::getAutoloader()->addClass('BLOGUS_BOL_PostService', $plugin->getBolDir() . DS . 'service.php');

OW::getRouter()->addRoute(new OW_Route('blogus.save.new', 'blogus/post/new', "BLOGUS_CTRL_Save", 'index'));
OW::getRouter()->addRoute(new OW_Route('blogus.save.edit', 'blogus/post/edit/:id', "BLOGUS_CTRL_Save", 'index'));

OW::getRouter()->addRoute(new OW_Route('blogus.post', 'blogus/post/:id', "BLOGUS_CTRL_View", 'index'));
OW::getRouter()->addRoute(new OW_Route('blogus.post-approve', 'blogus/post/approve/:id', "BLOGUS_CTRL_View", 'approve'));

// OW::getRouter()->addRoute(new OW_Route('post-part', 'blogus/post/:id/:part', "BLOGS_CTRL_View", 'index'));

OW::getRouter()->addRoute(new OW_Route('blogus.user-blog', 'blogus/user/:user', "BLOGUS_CTRL_UserBlog", 'index'));

OW::getRouter()->addRoute(new OW_Route('blogus.user-post', 'blogus/:id', "BLOGUS_CTRL_View", 'index'));

OW::getRouter()->addRoute(new OW_Route('blogus', 'blogus', "BLOGUS_CTRL_Blog", 'index', array('list' => array(OW_Route::PARAM_OPTION_HIDDEN_VAR => 'latest'))));
OW::getRouter()->addRoute(new OW_Route('blogus.list', 'blogus/list/:list', "BLOGUS_CTRL_Blog", 'index'));

// OW::getRouter()->addRoute(new OW_Route('blog-manage-posts', 'blogus/my-published-posts/', "BLOGS_CTRL_ManagementPost", 'index'));
// OW::getRouter()->addRoute(new OW_Route('blog-manage-drafts', 'blogus/my-drafts/', "BLOGS_CTRL_ManagementPost", 'index'));
// OW::getRouter()->addRoute(new OW_Route('blog-manage-comments', 'blogus/my-incoming-comments/', "BLOGS_CTRL_ManagementComment", 'index'));

// OW::getRouter()->addRoute(new OW_Route('blogs-admin', 'admin/blogus', "BLOGS_CTRL_Admin", 'index'));

