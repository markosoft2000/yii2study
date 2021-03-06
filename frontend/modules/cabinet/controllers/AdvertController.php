<?php

namespace app\modules\cabinet\controllers;

use common\controllers\AuthController;
use Yii;
use common\models\Advert;
use common\models\AdvertSearch;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\helpers\BaseFileHelper;
use Imagine\Image\Point;
use yii\imagine\Image;
use Imagine\Image\Box;

/**
 * AdvertController implements the CRUD actions for Advert model.
 */
class AdvertController extends AuthController
{
    public $layout = 'inner';

    public function init(){
        Yii::$app->view->registerJsFile(
            'http://maps.googleapis.com/maps/api/js?sensor=false',
            [
                'position' => \yii\web\View::POS_HEAD,
            ]
        );
    }

    /**
     * Lists all Advert models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new AdvertSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Advert model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Advert model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Advert();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            //return $this->redirect(['view', 'id' => $model->id]);
            return $this->redirect(['step2']);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Advert model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            //return $this->redirect(['view', 'id' => $model->id]);
            return $this->redirect(['step2']);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    public function actionStep2() {
        $id = Yii::$app->cache->get(Yii::$app->user->id . '__advert_id__');

        if (!$id) {
            $this->redirect(Url::to(['advert/']));
        }

        $model = Advert::findOne($id);
        $image = [];

        if ($general_image = $model->general_image) {
            $image[] =  '<img src="/uploads/adverts/' . $model->id . '/general/small_' . $general_image . '" width=250>';
        }

        if (Yii::$app->request->isPost) {
            $this->redirect(Url::to(['advert/']));
        }

        $path = Yii::getAlias("@frontend/web/uploads/adverts/" . $model->id);
        $images_add = [];

        try {
            if (is_dir($path)) {
                $files = \yii\helpers\FileHelper::findFiles($path);

                foreach ($files as $file) {
                    if (strstr($file, "small_") && !strstr($file, "general")) {
                        $images_add[] = '<img src="/uploads/adverts/' . $model->id . '/' . basename($file) . '" width=250>';
                    }
                }
            }
        }
        catch(\yii\base\Exception $e){}

        return $this->render("step2", ['model' => $model, 'image' => $image, 'images_add' => $images_add]);
    }

    public function actionFileUploadImage() {
        if (Yii::$app->request->isPost) {
            $id = Yii::$app->request->post("advert_id");
            $imgType = Yii::$app->request->post("advert_image_type");
            $path = Yii::getAlias("@frontend/web/" . Yii::$app->params['advertUploadDirectory'] . DIRECTORY_SEPARATOR . $id);

            switch ($imgType) {
                case 'general': {
                    $path .= DIRECTORY_SEPARATOR . "general";
                    BaseFileHelper::createDirectory($path);
                    $model = Advert::findOne($id);
                    $model->scenario = 'step2';

                    $file = UploadedFile::getInstance($model, 'general_image');

                    if (!$file) {
                        return false;
                    }

                    $name = 'general.' . $file->extension;
                    $file->saveAs($path . DIRECTORY_SEPARATOR . $name);

                    $model->general_image = $name;
                    $model->save();
                    break;
                }

                case 'extend': {
                    BaseFileHelper::createDirectory($path);
                    $file = UploadedFile::getInstanceByName('images');

                    if (!$file) {
                        return false;
                    }

                    $name = time() . '.' . $file->extension;
                    $file->saveAs($path . DIRECTORY_SEPARATOR . $name);
                    break;
                }

                default :
                    return false;
                    break;
            }


            $image = $path . DIRECTORY_SEPARATOR . $name;
            $new_name = $path . DIRECTORY_SEPARATOR . "small_" . $name;

            $size = getimagesize($image);
            $width = $size[0];
            $height = $size[1];

            Image::frame($image, 0, '666', 0)
                ->crop(new Point(0, 0), new Box($width, $height))
                ->resize(new Box(Yii::$app->params['uploadImageResizeX'], Yii::$app->params['uploadImageResizeY']))
                ->save($new_name, ['quality' => Yii::$app->params['uploadImageResizeQuality']]);

            if ($imgType == 'extend') {
                sleep(1);
            }

            return true;
        }
    }

    /**
     * Deletes an existing Advert model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Advert model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Advert the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Advert::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
