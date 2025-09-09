<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use croacworks\essentials\controllers\AuthorizationController;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $model croacworks\essentials\models\Folder */

$query = croacworks\essentials\models\File::find();
$dataProvider = new \yii\data\ActiveDataProvider([
    'query' => $query,
]);
//dd($_SERVER);
$query->andFilterWhere(['folder_id'=>$model->id]);
$models = $dataProvider->models;
$items = '';

foreach($models as $model){
    $items .= '            
    {
        src: "'. Yii::getAlias('@rootUrl').$model->url.'",
        type: "html5video",
        timeout: '.(($model->duration + 1) * 1000).',
        controls:false
    },';
}

$script = <<< JS
    $(function(){

        let instance = new Fancybox(
          [
            $items
          ],
          {
            Slideshow: {
                playOnStart: false,
                Navigation: false,
            },
            Carousel: {
                Navigation: false,
            },
            Fullscreen: {
                autoStart: true
            },
            autoSize : false,
            width:'100%',
            height:'100%',
            closeButton: false,
            on: {
                "Carousel.change": (fb,slide) => {
                    fancybox = Fancybox.getInstance();
                    fancybox.clearIdle;
                    if(fancybox.getSlide().index  <= (fancybox.carousel.slides.length - 1)){
                        console.log('Atual:' + fancybox.getSlide().index + ' Proximo: '  +  (fancybox.getSlide().index + 1) + ' Tamanho: ' + fancybox.carousel.slides.length);
                        setTimeout(() => {
                        fancybox.next();
                        }, fancybox.getSlide().timeout);
                    }else{
                        fancybox.jumpTo(0);
                    }

                },
                done: (fb, slide) => {
                    var vids = $("video"); 
                    $.each(vids, function(){
                        this.controls = false; 
                        this.width = 900;
                    }); 
                    fancybox = Fancybox.getInstance();
                    if( slide.index == 0){
                        console.log('Inicio: '  + slide.timeout);
                        console.log('Atual:' + fancybox.getSlide().index + ' Proximo: '  +  (fancybox.getSlide().index + 1) + ' Tamanho: ' + fancybox.carousel.slides.length);
                        fancybox = Fancybox.getInstance();
                        setTimeout(() => {
                            fancybox.next();
                        }, slide.timeout);
                    }
                }
            }
          }
        );
        // const fancybox = Fancybox.getInstance();
        //console.log(Fancybox.getInstance());

    });
JS;

$this::registerJs($script,$this::POS_END);

?>
<style>
    .fancybox__content{
        width: 100%!important;
        height: 100%!important;
        padding:0;
        margin: 0;
    }
</style>