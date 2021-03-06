<?php
namespace deka6pb\autoparser\components\PostingService\Abstraction;

use deka6pb\autoparser\models\Consumers;
use deka6pb\autoparser\models\Posts;
use Yii;
use yii\base\Exception;

abstract class APostingService implements IPostingService{
    private $_consumers = [];
    private $_enabledConsumers = [];
    private $_postCollection = [];
    private $_count;

    public function init() {
        $this->_count = (!empty(Yii::$app->controller->module->getMaxCountPosting())) ? Yii::$app->controller->module->getMaxCountPosting() : null;
        $this->initConsumers();
        $this->initPostCollection();
    }

    public function setCount($value) {
        $this->_count = $value;
    }

    public function initConsumers() {
        foreach ($this->_consumers AS $consumer) {
            if (!class_exists($consumer["class"])) {
                continue;
            }

            $component = Yii::createObject($consumer);
            if (!($component instanceof IPostDataConsumer)) {
                throw new Exception('This provider does not belong to the interface IPostDataProvider', 400);
            }

            if ((bool)$component->on != false) {
                $component->init();
                $this->_enabledConsumers[] = $component;
            }
        }
    }

    public function initPostCollection() {
        $model = new Posts();
        $this->_postCollection = $model->getNewPosts($this->_count);
    }

    public function run() {
        foreach ($this->_enabledConsumers AS $consumer) {
            //TODO ���������� ����������� ������
            //$consumer->SendInvites();
            if (!empty($this->_postCollection)) {
                $consumer->SendPosts($this->_postCollection);
            }
        }

        $this->afterRun();
    }

    public function afterRun() {
        foreach ($this->_postCollection AS $post) {
            $post->setPublished();
        }
    }

    public function getPostCollection() {
        return $this->_postCollection;
    }

    public function setConsumers() {
        $consumers = Consumers::find()->all();

        foreach ($consumers AS $objConsumer) {
            $this->_consumers[] = $objConsumer->getOptionsToArray();
        }
    }
}