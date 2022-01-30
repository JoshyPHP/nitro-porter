<?php

/**
 * Codoforum exporter tool. Tested with CodoForum v3.7.
 *
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL2
 * @author  Hans Adema
 */

namespace Porter\Package;

use Porter\ExportController;
use Porter\ExportModel;

class CodoForum extends ExportController
{
    public const SUPPORTED = [
        'name' => 'CodoForum',
        'prefix' => 'codo_',
        'charset_table' => 'posts',
        'features' => [
            'Users' => 1,
            'Passwords' => 1,
            'Categories' => 1,
            'Discussions' => 1,
            'Comments' => 1,
            'Polls' => 0,
            'Roles' => 1,
            'Avatars' => 0,
            'PrivateMessages' => 0,
            'Signatures' => 1,
            'Attachments' => 0,
            'Bookmarks' => 0,
            'Permissions' => 0,
            'UserNotes' => 0,
            'Ranks' => 0,
            'Groups' => 0,
            'Tags' => 0,
            'Reactions' => 0,
            'Articles' => 0,
        ]
    ];

    /**
     * @var array Required tables => columns
     */
    protected $sourceTables = array(
        'users' => array('id', 'username', 'mail', 'user_status', 'pass', 'signature'),
        'roles' => array('rid', 'rname'),
        'user_roles' => array('uid', 'rid'),
        'categories' => array('cat_id', 'cat_name'),
        'topics' => array('topic_id', 'cat_id', 'uid', 'title'),
        'posts' => array('post_id', 'topic_id', 'uid', 'imessage'),
    );

    /**
     * Main export process.
     *
     * @param ExportModel $ex
     * @see   $_structures in ExportModel for allowed destination tables & columns.
     */
    public function forumExport($ex)
    {
        $this->users($ex);
        $this->roles($ex);
        $this->userMeta($ex);
        $this->categories($ex);
        $this->discussions($ex);
        $this->comments($ex);
    }

    /**
     * @param ExportModel $ex
     */
    protected function users(ExportModel $ex): void
    {
        $ex->exportTable(
            'User',
            "
            select
                u.id as UserID,
                u.username as Name,
                u.mail as Email,
                u.user_status as Verified,
                u.pass as Password,
                'Vanilla' as HashMethod,
                from_unixtime(u.created) as DateFirstVisit
            from :_users u
         "
        );
    }

    /**
     * @param ExportModel $ex
     */
    protected function roles(ExportModel $ex): void
    {
        $ex->exportTable(
            'Role',
            "
            select
                r.rid as RolesID,
                r.rname as Name
            from :_roles r
        "
        );

        // User Role.
        $ex->exportTable(
            'UserRole',
            "
            select
                ur.uid as UserID,
                ur.rid as RoleID
            from :_user_roles ur
            where ur.is_primary = 1
        "
        );
    }

    /**
     * @param ExportModel $ex
     */
    protected function userMeta(ExportModel $ex): void
    {
        $ex->exportTable(
            'UserMeta',
            "
            select
                u.id as UserID,
                'Plugin.Signatures.Sig' as Name,
                u.signature as Value
            from :_users u
            where u.signature != '' and u.signature is not null"
        );
    }

    /**
     * @param ExportModel $ex
     */
    protected function categories(ExportModel $ex): void
    {
        $ex->exportTable(
            'Category',
            "
            select
                c.cat_id as CategoryID,
                c.cat_name as Name
            from :_categories c
        "
        );
    }

    /**
     * @param ExportModel $ex
     */
    protected function discussions(ExportModel $ex): void
    {
        $ex->exportTable(
            'Discussion',
            "
            select
                t.topic_id as DiscussionID,
                t.cat_id as CategoryID,
                t.uid as InsertUserID,
                t.title as Name,
                from_unixtime(t.topic_created) as DateInserted,
                from_unixtime(t.last_post_time) as DateLastComment
            from :_topics t
        "
        );
    }

    /**
     * @param ExportModel $ex
     */
    protected function comments(ExportModel $ex): void
    {
        $ex->exportTable(
            'Comment',
            "
            select
                p.post_id as CommentID,
                p.topic_id as DiscussionID,
                p.uid as InsertUserID,
                p.imessage as Body,
                'Markdown' as Format,
                from_unixtime(p.post_created) as DateInserted
            from :_posts p
        "
        );
    }
}
