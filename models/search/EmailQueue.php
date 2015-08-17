<?php

namespace itzen\mailer\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use itzen\mailer\models\EmailQueue as EmailQueueModel;

/**
 * EmailQueue represents the model behind the search form about `itzen\mailer\models\EmailQueue`.
 */
class EmailQueue extends EmailQueueModel
{
    public function rules()
    {
        return [
            [['id', 'user_id', 'max_attempts', 'attempt', 'priority', 'status'], 'integer'],
            [['category', 'from_name', 'from_address', 'to_name', 'to_address', 'subject', 'body', 'alternative_body', 'headers', 'attachments', 'sent_time', 'create_time', 'update_time'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = EmailQueueModel::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $dataProvider->sort->defaultOrder = [
            'id' => SORT_DESC
        ];


        if (!($this->load($params) && $this->validate())) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'user_id' => $this->user_id,
            'max_attempts' => $this->max_attempts,
            'attempt' => $this->attempt,
            'priority' => $this->priority,
            'status' => $this->status,
            'sent_time' => $this->sent_time,
            'create_time' => $this->create_time,
            'update_time' => $this->update_time,
        ]);

        $query->andFilterWhere(['like', 'category', $this->category])
            ->andFilterWhere(['like', 'from_name', $this->from_name])
            ->andFilterWhere(['like', 'from_address', $this->from_address])
            ->andFilterWhere(['like', 'to_name', $this->to_name])
            ->andFilterWhere(['like', 'to_address', $this->to_address])
            ->andFilterWhere(['like', 'subject', $this->subject])
            ->andFilterWhere(['like', 'body', $this->body])
            ->andFilterWhere(['like', 'alternative_body', $this->alternative_body])
            ->andFilterWhere(['like', 'headers', $this->headers])
            ->andFilterWhere(['like', 'attachments', $this->attachments]);

        return $dataProvider;
    }
}
