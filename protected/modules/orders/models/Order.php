<?php

Yii::import('orders.OrdersModule');

/**
 * This is the model class for table "Order".
 *
 * The followings are the available columns in table 'Order':
 * @property integer $id
 * @property integer $user_id
 * @property integer $delivery_id
 * @property float $delivery_price
 * @property float $total_price
 * @property integer $status_id
 * @property integer $paid
 * @property string $user_name
 * @property string $user_email
 * @property string $user_address
 * @property string $user_phone
 * @property string $user_comment
 * @property string $ip_address
 * @property string $created
 * @property string $updated
 */
class Order extends BaseModel
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Order the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'Order';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array('user_name, user_email, delivery_id', 'required'),
			array('user_name, user_email', 'length', 'max'=>100),
			array('user_phone', 'length', 'max'=>30),
			array('user_email', 'email'),
			array('user_comment', 'length', 'max'=>500),
			array('user_address', 'length', 'max'=>255),
			array('delivery_id', 'validateDelivery'),
			array('status_id', 'validateStatus'),
			//array('paid', 'boolean', 'on'=>'update'),
			// Search
			array('id, user_id, delivery_id, delivery_price, total_price, status_id, paid, user_name, user_email, user_address, user_phone, user_comment, ip_address, created, updated', 'safe', 'on'=>'search'),
		);
	}

	public function relations()
	{
		return array(
			'products'=>array(self::HAS_MANY, 'OrderProduct', 'order_id'),
			'status'=>array(self::BELONGS_TO, 'OrderStatus', 'status_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id'             => 'ID',
			'user_id'        => Yii::t('OrdersModule.core','Пользователь'),
			'delivery_id'    => Yii::t('OrdersModule.core','Способ доставки'),
			'delivery_price' => Yii::t('OrdersModule.core','Цена доставки'),
			'total_price'    => Yii::t('OrdersModule.core','Cумма товаров'),
			'full_price'     => Yii::t('OrdersModule.core','К оплате'),
			'status_id'      => Yii::t('OrdersModule.core','Статус'),
			'paid'           => Yii::t('OrdersModule.core','Оплачен'),
			'user_name'      => Yii::t('OrdersModule.core','Имя'),
			'user_email'     => Yii::t('OrdersModule.core','Email'),
			'user_address'   => Yii::t('OrdersModule.core','Адрес доставки'),
			'user_phone'     => Yii::t('OrdersModule.core','Телефон'),
			'user_comment'   => Yii::t('OrdersModule.core','Комментарий пользователя'),
			'ip_address'     => Yii::t('OrdersModule.core','IP адрес'),
			'created'        => Yii::t('OrdersModule.core','Дата создания'),
			'updated'        => Yii::t('OrdersModule.core','Дата обновления'),
		);
	}

	/**
	 * Check if delivery method exists
	 */
	public function validateDelivery()
	{
		if(StoreDeliveryMethod::model()->countByAttributes(array('id'=>$this->delivery_id)) == 0)
			$this->addError('delivery_id', Yii::t('OrdersModule.core', 'Необходимо выбрать способ доставки.'));
	}


	/**
	 * Check if status exists
	 */
	public function validateStatus()
	{
		if(OrderStatus::model()->countByAttributes(array('id'=>$this->status_id)) == 0)
			$this->addError('status_id', Yii::t('OrdersModule.core', 'Ошибка проверки статуса.'));
	}

	/**
	 * @return bool
	 */
	public function beforeSave()
	{
		if($this->isNewRecord)
		{
			$this->ip_address = Yii::app()->request->userHostAddress;
			$this->created = date('Y-m-d H:i:s');
		}
		$this->updated = date('Y-m-d H:i:s');

		// Set `New` status
		if(!$this->status_id)
			$this->status_id = 1;

		return parent::beforeSave();
	}

	/**
	 * @return bool
	 */
	public function afterDelete()
	{
		foreach($this->products as $ordered_product)
			$ordered_product->delete();

		return parent::afterDelete();
	}

	/**
	 * Update total
	 */
	public function updateTotalPrice()
	{
		$products = OrderProduct::model()->findAllByAttributes(array('order_id'=>$this->id));

		$this->total_price = $this->delivery_price;
		foreach($products as $p)
			$this->total_price += $p->price * $p->quantity;
		$this->save(false);
	}

	/**
	 * @return int
	 */
	public function updateDeliveryPrice()
	{
		$result = 0;
		$deliveryMethod = StoreDeliveryMethod::model()->findByPk($this->delivery_id);

		if($deliveryMethod)
		{
			if($deliveryMethod->price > 0)
			{
				if($deliveryMethod->free_from > 0 && $this->total_price > $deliveryMethod->free_from)
					$result = 0;
				else
					$result = $deliveryMethod->price;
			}
		}

		$this->delivery_price = $result;
		$this->save(false);
	}

	/**
	 * @return mixed
	 */
	public function getStatus_name()
	{
		if($this->status)
			return $this->status->name;
	}

	/**
	 * @return mixed
	 */
	public function getDelivery_name()
	{
		Yii::import('store.StoreModule');
		Yii::import('store.models.StoreDeliveryMethod');

		$model = StoreDeliveryMethod::model()->findByPk($this->delivery_id);
		if($model)
			return $model->name;
	}

	/**
	 * @return mixed
	 */
	public function getFull_price()
	{
		if(!$this->isNewRecord)
			return $this->total_price + $this->delivery_price;
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('delivery_id',$this->delivery_id);
		$criteria->compare('delivery_price',$this->delivery_price);
		$criteria->compare('total_price',$this->total_price);
		$criteria->compare('status_id',$this->status_id);
		$criteria->compare('paid',$this->paid);
		$criteria->compare('user_name',$this->user_name,true);
		$criteria->compare('user_email',$this->user_email,true);
		$criteria->compare('user_address',$this->user_address,true);
		$criteria->compare('user_phone',$this->user_phone,true);
		$criteria->compare('user_comment',$this->user_comment,true);
		$criteria->compare('ip_address',$this->ip_address,true);
		$criteria->compare('created',$this->created,true);
		$criteria->compare('updated',$this->updated,true);

		$sort=new CSort;
		$sort->defaultOrder = $this->getTableAlias().'.created ASC';

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
			'sort'=>$sort
		));
	}
}