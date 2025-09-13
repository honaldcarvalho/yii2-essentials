<?php
namespace croacworks\essentials\services;

use croacworks\essentials\models\Notification;
use Yii;
use yii\base\Component;
use yii\httpclient\Client;

class Notify extends Component
{
    /**
     * Cria uma notificação simples (mínimo viável in-app).
     */
    public function create(
        int $toUserId,
        string $title,
        ?string $content = null,
        string $type = 'system',
        ?string $url = null,
        ?int $groupId = null
    ): ?Notification {
        $n = new Notification([
            'group_id'       => $groupId,
            'recipient_id'   => $toUserId,
            'recipient_type' => 'user',
            'type'           => $type,
            'description'    => $title,
            'content'        => $content,
            'url'            => $url,
        ]);

        return $n->save() ? $n : null;
    }

    /**
     * Opcional: envia push via Expo quando houver token do usuário.
     */
    public function sendExpoPush(string $expoToken, string $title, string $body): bool
    {
        $client = new Client();
        $resp = $client->createRequest()
            ->setMethod('POST')
            ->setUrl('https://exp.host/--/api/v2/push')
            ->setHeaders(['Content-Type' => 'application/json'])
            ->setContent(json_encode(['to'=>$expoToken,'title'=>$title,'body'=>$body]))
            ->send();
        if (!$resp->isOk) {
            Yii::error(['expoPushError'=>$resp->content], 'notify');
        }
        return $resp->isOk;
    }
}
