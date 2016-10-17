<?php

namespace app\modules\main\controllers;

use frontend\components\Common;
use yii\web\Controller;

/**
 * Default controller for the `main` module
 */
class DefaultController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        $this->layout = 'bootstrap';
        return $this->render('index');
    }

    public function actionService() {
        $cache = \Yii::$app->cache;

        $cache->set('test', 123321);

        print $cache->get('test');
    }

    public function actionEvent() {
        //first way
        $component = new Common();
        //second way
//        $component = \Yii::$app->common;

        //...
        $component->on(Common::EVENT_NOTIFY, [$component, 'notifyAdmin'], "some data");
        $component->sendMail("test@domain.com", "Test", "Test body");
        $component->off(Common::EVENT_NOTIFY, [$component, 'notifyAdmin']);
//        $component->off(Common::EVENT_NOTIFY); // off all event handlers
    }

    public function actionPath(){
        // @yii
        // @app
        //@runtime
        //@webroot
        //@web
        //@vendor
        //@bower
        //@npm
        // @frontend
        // @backend

        print \Yii::getAlias('@test');
    }
}