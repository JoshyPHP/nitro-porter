<?php
/**
 * WebWiz exporter tool
 *
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL2
 * @author  Todd Burry
 */

namespace NitroPorter\Package;

use NitroPorter\ExportController;
use NitroPorter\ExportModel;

class WebWiz extends ExportController
{

    public const SUPPORTED = [
        'name' => 'Web Wiz Forums',
        'prefix' => 'tbl',
        'features' => [
            'Users' => 1,
            'Passwords' => 1,
            'Categories' => 1,
            'Discussions' => 1,
            'Comments' => 1,
            'Polls' => 0,
            'Roles' => 1,
            'Avatars' => 1,
            'PrivateMessages' => 1,
            'Signatures' => 1,
            'Attachments' => 0,
            'Bookmarks' => 0,
            'Permissions' => 0,
            'Badges' => 0,
            'UserNotes' => 0,
            'Ranks' => 0,
            'Groups' => 0,
            'Tags' => 0,
            'Reactions' => 0,
            'Articles' => 0,
        ]
    ];

    /**
     *
     * @param ExportModel $ex
     */
    public function forumExport($ex)
    {

        $characterSet = $ex->getCharacterSet('Topic');
        if ($characterSet) {
            $ex->characterSet = $characterSet;
        }

        $ex->beginExport('', 'Web Wiz Forums');
        $ex->sourcePrefix = 'tbl';

        //      // Permissions.
        //      $Permission_Map = array(
        //          'group_id' => 'RoleID',
        //          'can_access_cp' => 'Garden.Settings.View',
        //          'can_access_edit' => 'Vanilla.Discussions.Edit',
        //          'can_edit_all_comments' => 'Vanilla.Comments.Edit',
        //          'can_access_admin' => 'Garden.Settings.Manage',
        //          'can_admin_members' => 'Garden.Users.Edit',
        //          'can_moderate_comments' => 'Garden.Moderation.Manage',
        //          'can_view_profiles' => 'Garden.Profiles.View',
        //          'can_post_comments' => 'Vanilla.Comments.Add',
        //          'can_view_online_system' => 'Vanilla.Discussions.View',
        //          'can_sign_in' => 'Garden.SignIn.Allow',
        //          'can_view_profiles3' => 'Garden.Activity.View',
        //          'can_post_comments2' => 'Vanilla.Discussions.Add'
        //      );
        //      $Permission_Map = $ex->FixPermissionColumns($Permission_Map);
        //      foreach ($Permission_Map as $Column => &$Info) {
        //         if (is_array($Info) && isset($Info['Column']))
        //            $Info['Filter'] = array($this, 'Bool');
        //      }
        //
        //      $ex->ExportTable('Permission', "
        //         select
        //            g.can_view_profiles as can_view_profiles2,
        //            g.can_view_profiles as can_view_profiles3,
        //            g.can_post_comments as can_post_comments2,
        //            g.can_post_comments as can_sign_in,
        //            case when can_access_admin = 'y' then 'all' when can_view_online_system = 'y' then 'view' end as _Permissions,
        //            g.*
        //         from forum_member_groups g
        //      ", $Permission_Map);


        // User.
        $user_Map = array(
            'Author_ID' => 'UserID',
            'Username' => array('Column' => 'Name', 'Filter' => 'HTMLDecoder'),
            'Real_name' => array('Column' => 'FullName', 'Type' => 'varchar(50)', 'Filter' => 'HTMLDecoder'),
            'Password2' => 'Password',
            'Gender2' => 'Gender',
            'Author_email' => 'Email',
            'Photo2' => array('Column' => 'Photo', 'Filter' => 'HTMLDecoder'),
            'Login_IP' => 'LastIPAddress',
            'Banned' => 'Banned',
            'Join_date' => array('Column' => 'DateInserted'),
            'Last_visit' => array('Column' => 'DateLastActive'),
            'Location' => array('Column' => 'Location', 'Filter' => 'HTMLDecoder'),
            'DOB' => 'DateOfBirth',
            'Show_email' => 'ShowEmail'
        );
        $ex->exportTable(
            'User', "
         select
            concat(Salt, '$', Password) as Password2,
            case u.Gender when 'Male' then 'm' when 'Female' then 'f' else 'u' end as Gender2,
         case when Avatar like 'http%' then Avatar when Avatar > '' then concat('webwiz/', Avatar) else null end as Photo2,
            'webwiz' as HashMethod,
            u.*
         from :_Author u
         ", $user_Map
        );


        // Role.
        $role_Map = array(
            'Group_ID' => 'RoleID',
            'Name' => 'Name'
        );
        $ex->exportTable(
            'Role', "
         select *
         from :_Group", $role_Map
        );

        // User Role.
        $userRole_Map = array(
            'Author_ID' => 'UserID',
            'Group_ID' => 'RoleID'
        );
        $ex->exportTable(
            'UserRole', "
         select u.*
         from :_Author u", $userRole_Map
        );

        // UserMeta
        $ex->exportTable(
            'UserMeta', "
         select
            Author_ID as UserID,
            'Plugin.Signatures.Sig' as `Name`,
            Signature as `Value`
         from :_Author
         where Signature <> ''"
        );

        // Category.
        $category_Map = array(
            'Forum_ID' => 'CategoryID',
            'Forum_name' => 'Name',
            'Forum_description' => 'Description',
            'Parent_ID' => 'ParentCategoryID',
            'Forum_order' => 'Sort'
        );
        $ex->exportTable(
            'Category', "
         select
            f.Forum_ID,
            f.Cat_ID * 1000 as Parent_ID,
            f.Forum_order,
            f.Forum_name,
            f.Forum_description
         from :_Forum f

         union all

         select
            c.Cat_ID * 1000,
            null,
            c.Cat_order,
            c.Cat_name,
            null
         from :_Category c
         ", $category_Map
        );

        // Discussion.
        $discussion_Map = array(
            'Topic_ID' => 'DiscussionID',
            'Forum_ID' => 'CategoryID',
            'Author_ID' => 'InsertUserID',
            'Subject' => array('Column' => 'Name', 'Filter' => 'HTMLDecoder'),
            'IP_addr' => 'InsertIPAddress',
            'Message' => array('Column' => 'Body'),
            'Format' => 'Format',
            'Message_date' => array('Column' => 'DateInserted'),
            'No_of_views' => 'CountViews',
            'Locked' => 'Closed',

        );
        $ex->exportTable(
            'Discussion', "
         select
            th.Author_ID,
            th.Message,
            th.Message_date,
            th.IP_addr,
            'Html' as Format,
            t.*
         from :_Topic t
         join :_Thread th
            on t.Start_Thread_ID = th.Thread_ID", $discussion_Map
        );

        // Comment.
        $comment_Map = array(
            'Thread_ID' => 'CommentID',
            'Topic_ID' => 'DiscussionID',
            'Author_ID' => 'InsertUserID',
            'IP_addr' => 'InsertIPAddress',
            'Message' => array('Column' => 'Body'),
            'Format' => 'Format',
            'Message_date' => array('Column' => 'DateInserted')
        );
        $ex->exportTable(
            'Comment', "
      select
         th.*,
         'Html' as Format
      from :_Thread th
      join :_Topic t
         on t.Topic_ID = th.Topic_ID
      where th.Thread_ID <> t.Start_Thread_ID", $comment_Map
        );

        $this->exportConversations();

        $ex->endExport();
    }

    public function exportConversations()
    {
        $ex = $this->ex;

        $this->_exportConversationTemps();

        // Conversation.
        $conversation_Map = array(
            'PM_ID' => 'ConversationID',
            'Title' => array('Column' => 'Subject', 'Type' => 'varchar(255)'),
            'Author_ID' => 'InsertUserID',
            'PM_Message_Date' => array('Column' => 'DateInserted')
        );
        $ex->exportTable(
            'Conversation', "
         select
            pm.*,
            g.Title
         from :_PMMessage pm
         join z_pmgroup g
            on pm.PM_ID = g.Group_ID;", $conversation_Map
        );

        // User Conversation.
        $userConversation_Map = array(
            'Group_ID' => 'ConversationID',
            'User_ID' => 'UserID'
        );
        $ex->exportTable(
            'UserConversation', "
         select
            g.Group_ID,
            t.User_ID
         from z_pmto t
         join z_pmgroup g
            on g.Group_ID = t.PM_ID;", $userConversation_Map
        );

        // Conversation Message.
        $message_Map = array(
            'Group_ID' => 'ConversationID',
            'PM_ID' => 'MessageID',
            'PM_Message' => 'Body',
            'Format' => 'Format',
            'PM_Message_Date' => array('Column' => 'DateInserted'),
            'Author_ID' => 'InsertUserID'
        );
        $ex->exportTable(
            'ConversationMessage', "
         select
            pm.*,
            pm2.Group_ID,
            'Html' as Format
          from :_PMMessage pm
          join z_pmtext pm2
            on pm.PM_ID = pm2.PM_ID;", $message_Map
        );
    }

    protected function _exportConversationTemps()
    {
        $sql = "
         drop table if exists z_pmto;

         create table z_pmto (
            PM_ID int unsigned,
            User_ID int,
            primary key(PM_ID, User_ID)
            );

         insert ignore z_pmto (
            PM_ID,
            User_ID
         )
         select
            PM_ID,
            Author_ID
         from :_PMMessage;

         insert ignore z_pmto (
            PM_ID,
            User_ID
         )
         select
            PM_ID,
            From_ID
         from :_PMMessage;

         drop table if exists z_pmto2;
         create table z_pmto2 (
            PM_ID int unsigned,
             UserIDs varchar(250),
             primary key (PM_ID)
         );

         replace z_pmto2 (
            PM_ID,
            UserIDs
         )
         select
            PM_ID,
            group_concat(User_ID order by User_ID)
         from z_pmto
         group by PM_ID;

         drop table if exists z_pmtext;
         create table z_pmtext (
            PM_ID int unsigned,
            Title varchar(250),
             Title2 varchar(250),
             UserIDs varchar(250),
             Group_ID int unsigned
         );

         insert z_pmtext (
            PM_ID,
            Title,
            Title2
         )
         select
            PM_ID,
            PM_Tittle,
            case when PM_Tittle like 'Re:%' then trim(substring(PM_Tittle, 4)) else PM_Tittle end as Title2
         from :_PMMessage;

         create index z_idx_pmtext on z_pmtext (PM_ID);

         update z_pmtext pm
         join z_pmto2 t
            on pm.PM_ID = t.PM_ID
         set pm.UserIDs = t.UserIDs;

         drop table if exists z_pmgroup;

         create table z_pmgroup (
                 Group_ID int unsigned,
                 Title varchar(250),
                 UserIDs varchar(250)
               );

         insert z_pmgroup (
                 Group_ID,
                 Title,
                 UserIDs
               )
               select
                 min(pm.PM_ID),
                 pm.Title2,
                 t2.UserIDs
               from z_pmtext pm
               join z_pmto2 t2
                 on pm.PM_ID = t2.PM_ID
               group by pm.Title2, t2.UserIDs;

         create index z_idx_pmgroup on z_pmgroup (Title, UserIDs);
         create index z_idx_pmgroup2 on z_pmgroup (Group_ID);

         update z_pmtext pm
               join z_pmgroup g
                 on pm.Title2 = g.Title and pm.UserIDs = g.UserIDs
               set pm.Group_ID = g.Group_ID;";

        $this->ex->queryN($sql);
    }
}

