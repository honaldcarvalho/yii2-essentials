<?php
namespace croacworks\essentials\modules\textrank;

use Yii;

/**
 * TextRank module for CroacWorks Essentials
 *
 * @property string $dataPath Diretório persistente para cache adaptativo
 */
class Module extends \yii\base\Module
{
    /**
     * Caminho base para armazenar dados persistentes do TextRank (boost, logs, etc).
     * Pode ser configurado no módulo (ver web.php).
     *
     * @var string|false
     */
    public $dataPath = '@common/data/textrank';

    public function init()
    {
        parent::init();

        // Define alias global do caminho de dados
        if ($this->dataPath) {
            $resolved = Yii::getAlias($this->dataPath);
            Yii::setAlias('@textrankData', $resolved);

            if (!is_dir($resolved)) {
                @mkdir($resolved, 0775, true);
            }
        }
    }
}
