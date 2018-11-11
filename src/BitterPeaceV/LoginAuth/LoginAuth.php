<?php

namespace BitterPeaceV\LoginAuth;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class LoginAuth extends PluginBase
{
    private $messages = [];
    public $register = [];
    public $login = [];

    public function onEnable()
    {
        // メッセージリソースの読み込み
        $handle = $this->getResource("message.json");
        $this->messages = json_decode(stream_get_contents($handle), true);
        fclose($handle);

        // プラグイン専用フォルダを作る
        $folder = $this->getDataFolder();
        if (!file_exists($folder)) @mkdir($folder);

        // データベースに接続
        Database::openDatabase($folder . "loginauth.db");

        // プラグインマネージャーに登録してイベントを受信
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    public function onDisable()
    {
        Database::closeDatabase();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        // コマンドを実行したのがプレイヤーでない場合
        if (!($sender instanceof Player)) {
            $sender->sendMessage($this->getMessage("player.only"));

            return true;
        }

        // login, register又はパスワードが入力されてない場合
        if (!isset($args[0]) || !isset($args[1])) {
            return false;
        }

        $name = $sender->getName();
        switch ($args[0]) {
            case "register":
                // データ登録が必要な人リストにプレイヤーの名前がある場合
                if (\in_array($name, $this->register)) {
                    // 半角英数字でない場合
                    if (!preg_match("/^[a-zA-Z0-9]+$/", $args[1])) {
                        return false;
                    }
                    
                    // パスワードによるプレイヤーのUUIDの暗号化
                    $data = CipherUtility::encrypt($sender->getUniqueId()->toString(), $args[1]);

                    // 登録が成功した場合
                    if (Database::registerUserData($name, $data[0], \bin2hex($data[1]))) {
                        // リストから名前を削除
                        foreach ($this->register as $key => $val) {
                            if ($val == $name) {
                                unset($this->register[$key]);
                            }
                        }
                        array_values($this->register);

                        $sender->sendMessage($this->getMessage("register.complete", [$args[1]]));
                        $sender->setImmobile(false);

                        return true;
                    } else {
                        $sender->sendMessage($this->getMessage("register.failed"));

                        // データベースに接続
                        Database::openDatabase($this->getDataFolder() . "loginauth.db");

                        return true;
                    }
                } else {
                    $sender->sendMessage($this->getMessage("register.not.required"));

                    return true;
                }
                break;
            case "login":
                // ログインが必要な人リストにプレイヤーの名前がある場合
                if (\in_array($name, $this->login)) {
                    // 半角英数字でない場合
                    if (!preg_match("/^[a-zA-Z0-9]+$/", $args[1])) {
                        return false;
                    }
                    
                    // データベースからプレイヤーのデータを取得する
                    $data = Database::getUserData($name);

                    // データが揃っている場合
                    if (!empty($data)) {
                        // プレイヤーのUUIDの復号化
                        $uuid = CipherUtility::decrypt($data["uuid"], $args[1], \hex2bin($data["iv"]));

                        // 復号化したUUIDとプレイヤーのUUIDが等しい場合
                        if ($sender->getUniqueId()->toString() == $uuid) {
                            // リストから名前を削除
                            foreach ($this->login as $key => $val) {
                                if ($val == $name) {
                                    unset($this->login[$key]);
                                }
                            }
                            array_values($this->login);
                            
                            $sender->sendMessage($this->getMessage("login.success"));
                            $sender->setImmobile(false);
                            
                            return true;
                        } else {
                            $sender->kick($this->getMessage("login.failed"), false);

                            return true;
                        }
                    } else {
                        // リストから名前を削除
                        foreach ($this->login as $key => $val) {
                            if ($val == $name) {
                                unset($this->login[$key]);
                            }
                        }
                        array_values($this->login);

                        // 登録が必要な人リストに名前を入れる
                        $this->register[] = $name;

                        return true;
                    }
                } else {
                    $sender->sendMessage($this->getMessage("login.not.required"));

                    return true;
                }
                break;
            default: return false;
        }
    }

    /**
     * メッセージの取得
     * 
     * @param string $key   メッセージキー
     * @param array  $param 変数に代入する値
     * 
     * @return string
     */
    public function getMessage(string $key, array $params = []): string
    {
        $message = $this->messages[$key];
        $search = ["%FIX%"];
        $replace = ["&d[LoginAuth]&f"];

        for ($i = 0; $i < \count($params); $i++) {
            $search[] = "%" . ($i + 1);
            $replace[] = $params[$i];
        }

        // メッセージの変数に値を代入し、色を付けた文字列を返す
        return TextFormat::colorize(\str_replace($search, $replace, $message));
    }
}
