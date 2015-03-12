<?php

namespace itzen\mailer\controllers;

use Yii;
use itzen\mailer\models\EmailQueue;
use itzen\mailer\models\search\EmailQueue as EmailQueueSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * EmailQueueController implements the CRUD actions for EmailQueue model.
 */
class EmailQueueController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all EmailQueue models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new EmailQueueSearch;
        $dataProvider = $searchModel->search(Yii::$app->request->getQueryParams());

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'searchModel' => $searchModel,
        ]);
    }

    /**
     * Displays a single EmailQueue model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('view', ['model' => $model]);
        }
    }
   
    
    /**
     * Displays or updates a single EmailQueue model.
     * @param integer $id
     * @return mixed
     */
    public function actionPartialView($id = null)
    {
        if ($id === null) {
            if (isset($_POST['expandRowKey'])) {
                $id = (int) $_POST['expandRowKey'];
            }
            if (isset($_POST['EmailQueue']['id'])) {
                $id = (int) $_POST['EmailQueue']['id'];
            }
        }

        $model = $this->findModel($id);

        if (isset($_POST['EmailQueue'])) {
            if ($model->load(Yii::$app->request->post()) && $model->save()) {
                return "success";
            }
            else{
                $this->renderPartial('_view', ['model' => $model]);
            }
        }

        return $this->renderPartial('_view', ['model' => $model]);
    }
    
    /**
     * Creates a new EmailQueue model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new EmailQueue;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing EmailQueue model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing EmailQueue model.
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
     * Finds the EmailQueue model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return EmailQueue the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = EmailQueue::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
