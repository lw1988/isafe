<?php
/**
 * Created by PhpStorm.
 * User: Metersbonwe
 * Date: 2016/5/16
 * Time: 15:43
 */
namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\SafeList;
use app\models\SafeExt;
use app\models\User;

class ListController extends Controller
{
    public $enableCsrfValidation = false;

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionList()
    {
        $params = $p = Yii::$app->request->get();
        $status_arr = array(1 => '新建', 2 => '进行中', 3 => '已取消', 4 => '已完成');
        $page_size = 15;
        $list_info = $this->getListInfo($params, isset($params['page']) ? $params['page'] : 1, $page_size);
        $pages = ceil($list_info['row_num'] / $page_size);
        return $this->render('list', ['list_info' => $list_info['list_info'], 'status_arr' => $status_arr, 'params' => $p, 'pages' => $pages > 1 ? $pages : 1, 'page' => isset($params['page']) ? $params['page'] : 1]);
    }

    public function getListInfo($conditions = array(), $page = 1, $page_size = 15)
    {
        $conditions['username'] = isset($conditions['username']) ? trim($conditions['username']) : '';
        if (!empty($conditions['username'])) {
//            $user_info = User::find()->where(['chinese_name' => $conditions['username']])->asArray()->one();
            $user_info = User::find()->where(['like', 'chinese_name', $conditions['username']])->asArray()->all();

            if (!empty($user_info)) {
                $safe_id_arr = array();
                $safe_ids_new = '';
                foreach ($user_info as $value) {
                    $safe_ext_info = SafeExt::find()->where(['user_id' => $value['id']])->asArray()->all();
                    if (!empty($safe_ext_info)) {
                        foreach ($safe_ext_info as $item) {
                            $safe_id_arr[] = $item['safe_id'];
                        }
                        $safe_ids = implode(',', $safe_id_arr);
                    }

                    if (!empty($conditions['status'])) {
                        $safe_info = SafeList::find()->where(['and', ['in', 'id', $safe_id_arr], ['in', 'status', $conditions['status']]])->asArray()->all();
                        if ($safe_info) {
                            foreach ($safe_info as $item) {
                                $safe_id_arr_new[] = $item['id'];
                            }
                            $safe_ids = implode(',', $safe_id_arr_new);
                        } else {
                            unset($safe_ids);
                        }
                    }
                }
                if(isset($safe_ids)){
                    $safe_ids_new .= $safe_ids . ',';
                    $safe_ids = substr($safe_ids_new, 0, -1);
                }

            }
        }

        if (empty($conditions['username'])) {
            if (!empty($conditions['status'])) {
                $safe_info = SafeList::find()->where(['in', 'status', $conditions['status']])->asArray()->all();
                if ($safe_info) {
                    foreach ($safe_info as $item) {
                        $safe_id_arr[] = $item['id'];
                    }
                    $safe_ids = implode(',', $safe_id_arr);
                }
            } else {
                $safe_ids = '';
            }
        }

        $connection = Yii::$app->db;
        $sql = "select a.*,c.chinese_name from safe_list a,safe_ext b,`user` c where a.id=b.safe_id and b.user_id=c.id";
        $offset = ($page - 1) * $page_size;

        if (isset($safe_ids)) {
            if (!empty($safe_ids)) {
                $sql_ext = " and a.id in (" . $safe_ids . ")";
                $sql = $sql . $sql_ext;
                $row_num = count($connection->createCommand($sql)->queryAll());
                $list_info = $connection->createCommand("$sql GROUP BY a.id order by a.id desc limit $offset,$page_size")->queryAll();
            } else {
                $row_num = count($connection->createCommand($sql)->queryAll());
                $list_info = $connection->createCommand("$sql GROUP BY a.id order by a.id desc limit $offset,$page_size")->queryAll();
            }
        } else {
            $row_num = 0;
            $list_info = array();
        }
        return ['row_num' => $row_num, 'list_info' => $list_info];
    }
}