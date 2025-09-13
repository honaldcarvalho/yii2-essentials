<?php
namespace croacworks\essentials\services;

use croacworks\essentials\models\Notification;
use croacworks\essentials\controllers\AuthorizationController;

class NotificationService
{
    public static function notify(int $userId, string $title, ?string $body=null, ?string $url=null, ?int $groupId=null): ?Notification
    {
        $n = new Notification([
            'user_id'    => $userId,
            'group_id'   => $groupId ?: AuthorizationController::userGroup(),
            'title'      => $title,
            'body'       => $body,
            'url'        => $url,
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $n->save(false) ? $n : null;
    }
}
