<?php
/**
 * Created by PhpStorm.
 * User: USER
 * Date: 7/27/2017
 * Time: 8:41 AM
 */

namespace Strud\Helper\Authentication;


use Strud\Database\Expression\Criteria;
use Strud\Database\Expression\Join;
use Strud\Database\Expression\On;
use Strud\Database\Model\Column;
use Strud\Database\Model\Table;
use Strud\Route\Component\Map;

class AuthMap implements Map
{
    const COLUMN_USER_TABLE = "users";
    const COLUMN_REMEMBER_TABLE = "users_remembered";
    const COLUMN_CONFIRMATION_TABLE = "users_confirmations";
    const COLUMN_RESET_TABLE = "users_resets";
    const COLUMN_THROTTLING_TABLE = "users_throttling";
    const COLUMN_USER_ID = "user_id";
    const COLUMN_ID = "id";
    const COLUMN_PASSWORD = "password";
    const COLUMN_EMAIL = "email";
    const COLUMN_USERNAME = "username";
    const COLUMN_STATUS = "status";
    const COLUMN_VERIFIED = "verified";
    const COLUMN_REGISTERED = "registered";
    const COLUMN_LAST_LOGIN = "last_login";
    const COLUMN_SELECTOR = "selector";
    const COLUMN_TOKEN = "token";
    const COLUMN_USER = "user";
    const COLUMN_EXPIRES = "expires";
    const COLUMN_ACTION_TYPE = "action_type";
    const COLUMN_ATTEMPTS = "attempts";
    const COLUMN_TIME_BUCKET = "time_bucket";

    public static function getTable($alias = '')
    {
        $table =  new Table(self::COLUMN_USER_TABLE);
        $table->addColumn(new Column(self::COLUMN_USER_ID, "userId"));
        $table->addColumn(new Column(self::COLUMN_EMAIL, "email"));
        $table->addColumn(new Column(self::COLUMN_PASSWORD, "password"));
        $table->addColumn(new Column(self::COLUMN_USERNAME, "username"));
        $table->addColumn(new Column(self::COLUMN_STATUS, "status"));
        $table->addColumn(new Column(self::COLUMN_REGISTERED, "registered"));
        $table->addColumn(new Column(self::COLUMN_LAST_LOGIN, "lastLogin"));
        $table->addColumn(new Column(self::COLUMN_VERIFIED, "verified"));

        return $table;
    }

    public static function getUserRememberedTable($alias = '')
    {
        $table =  new Table(self::COLUMN_REMEMBER_TABLE);
        $table->addColumn(new Column(self::COLUMN_ID, "rememberedId"));
        $table->addColumn(new Column(self::COLUMN_USER, "user"));
        $table->addColumn(new Column(self::COLUMN_SELECTOR, "selector"));
        $table->addColumn(new Column(self::COLUMN_TOKEN, "token"));
        $table->addColumn(new Column(self::COLUMN_EXPIRES, "expires"));

        return $table;
    }

    public static function getUserThrottlingTable($alias = '')
    {
        $table =  new Table(self::COLUMN_THROTTLING_TABLE);
        $table->addColumn(new Column(self::COLUMN_ID, "throttlingId"));
        $table->addColumn(new Column(self::COLUMN_ACTION_TYPE, "action_type"));
        $table->addColumn(new Column(self::COLUMN_SELECTOR, "selector"));
        $table->addColumn(new Column(self::COLUMN_TIME_BUCKET, "time_bucket"));
        $table->addColumn(new Column(self::COLUMN_ATTEMPTS, "attempts"));

        return $table;
    }

    public static function getUserResetTable($alias = '')
    {
        $table =  new Table(self::COLUMN_REMEMBER_TABLE);
        $table->addColumn(new Column(self::COLUMN_ID, "resetId"));
        $table->addColumn(new Column(self::COLUMN_USER, "user"));
        $table->addColumn(new Column(self::COLUMN_SELECTOR, "selector"));
        $table->addColumn(new Column(self::COLUMN_TOKEN, "token"));

        return $table;
    }

    public static function getConfirmationTable($alias= '')
    {
        $table =  new Table(self::COLUMN_CONFIRMATION_TABLE);
        $table->addColumn(new Column(self::COLUMN_ID, "confirmationId"));
        $table->addColumn(new Column(self::COLUMN_EMAIL, "email"));
        $table->addColumn(new Column(self::COLUMN_SELECTOR, "selector"));
        $table->addColumn(new Column(self::COLUMN_TOKEN, "token"));
        $table->addColumn(new Column(self::COLUMN_EXPIRES, "expires"));

        return $table;
    }

    public static function getJoinExpression()
    {
        return new Join(self::getTable(), new On(self::COLUMN_USER, Criteria::EQUALS_TO, self::COLUMN_ID, self::getUserRememberedTable()->getQualifiedNameWithAlias(), self::getTable()->getQualifiedNameWithAlias()));
    }
}