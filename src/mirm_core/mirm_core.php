<?php
namespace amatya_core;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class mirm_core extends PluginBase implements Listener
{
    public function onEnable()
    {
        Server::getInstance()->getPluginManager()->registerEvents($this,$this);

        if(!file_exists($this->getDataFolder())){
            mkdir($this->getDataFolder(), 0744, true);
        }
        
        kaitoku tuikao
        
        global $config;
        $config = new Config($this->getDataFolder() . "configcore.yml", Config::YAML,
            array("これは設定です"=>array("HP"=>100,"aコアのid"=>100,"bコアのid"=>100,"TPのブロックのid"=>100,  コア破壊得点: 1
  キル得点: 1
  ゲームモード: 1,"a座標のX"=>100,"a座標のY"=>100,"a座標のZ"=>100,"b座標のX"=>100,"b座標のY"=>100,"b座標のZ"=>100)
                )
        );
        global $config2;
        $config2 = new Config($this->getDataFolder() . "configlevel.yml", Config::YAML,
            array('プレイヤー名' =>array("kill"=>0,"level"=>0),
                "これは設定です"=>array("xレベルの時にいくらもらえるか"=>100)
            )
        );


economys
        if(Server::getInstance()->getPluginManager()->getPlugin("PocketMoney") !=null) {
            $this->pocketmoney = $this->getServer()->getPluginManager()->getPlugin("PocketMoney");
        }else{
            Server::getInstance()->getLogger()->info("pocketmoneyが読み込めませんでした");
        }
        ///
        $this->team = [1 => [] , 2 => [] ];
        $this->joinedpvp = array();
        $this->teamcore =array();

        //teamcore代入
        $c =$config->get("これは設定です");
        $this->teamcore[1]=$c["HP"];
        $this->teamcore[2]=$c["HP"];
        ////
        Server::getInstance()->getLogger()->info("mirm-coreが読み込まれました");
    }

    public function onDisable()
    {
        unset($this->players);
        unset($this->joined);
        unset($this->team);
        foreach ($this->getServer()->getOnlinePlayers() as $p) {
            $p->kick("鯖がリロードまたは終了しました。");
        }
    }


    /*コアの方は
座標、HP、コアにするブロックのID、チーム分け&TPのブロックIDは、configから帰れるようにしてもらって。
コアを壊されたら写真にあるところらへんに、§e壊した人の名前§fがチーム名のコアを破壊しています！
残りHPというふうに表示、そして、コアの体力がなくなったら全員リスポに戻されてワープできなくなって３０秒後に再起動という形にして欲しいです！
チーム分けは、出撃する時にブロックタップして、チーム分けされてそしてそのチームの決めた座標にTPという形にして欲しいです

そして、チーム分けのブロックをタップしたら

君は○○チームだ健闘を祈る！

という表示をして欲しいです


   */
    public function oninteract(PlayerInteractEvent $event){
        global $config;

        $blockid = $event->getBlock()->getId();
        $player = $event->getPlayer();
        $name = $player->getName();
        echo $blockid;
        if(isset($this->end) && $this->end){
            return;
        }
        
        $a = $config->get("これは設定です");
        if ($blockid == $a["TPのブロックのid"]){
            unset($blockid );
            echo 1;
            if(isset($this->joinedpvp[$name] )){
                echo 2;
                if($this->joinedpvp[$name] == 1 ){
                    $player->sendMessage(TextFormat::RED ."[PVP]あなたはすでにpvpに参加しています。");
                    return;
                }
            }
            $this->joinedpvp[$name] =1;
            if(count($this->team[1]) <= count($this->team[2])){
                $this->team[1][$name] = 1;
                unset( $this->team[2][$name]);
                $teamname = "TeamA";
                $c =$config->get("これは設定です");
                $pos = new  Vector3($c["a座標のX"],$c["a座標のY"],$c["a座標のZ"]);
            }else{
                $this->team[2][$name] = 1;
                unset( $this->team[1][$name]);
                $teamname = "TeamB";
                $c =$config->get("これは設定です");
                $pos = new  Vector3($c["b座標のX"],$c["b座標のY"],$c["b座標のZ"]);
            }

            $player->sendMessage("君は".$teamname."チームだ健闘を祈る！");
            $player->teleport($pos);
            $this->setTitle($player);
        }
    }

    public function onbreak(BlockBreakEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        global $config;

        if($event->getBlock()->getID() == $config->get("aコアのid")||$event->getBlock()->getID() == $config->get("bコアのid")){
            $event->setCancelled(true);
            if($event->getBlock()->getID() == $config->get("aコアのid") and isset($this->team[2][$name])){
                $teamname="TeamA";
                if($this->teamcore[1] ==0){
                    $teamname ="TeamB";
                    $ok=1;
                }
            }
            elseif($event->getBlock()->getID() == $config->get("bコアのid") and isset($this->team[1][$name])){
                $teamname="TeamB";
                if($this->teamcore[2] ==0){
                    $ok=1;
                    $teamname ="TeamA";
                }
            }else{
                $ng =1;
                $players = Server::getInstance()->getOnlinePlayers();
                foreach ($players as $player) {
                    $player->sendPopUp(TextFormat::YELLOW.$name."が自チームのコアを攻撃。");
                }
            }
            if(!isset($ng)){
                $players = Server::getInstance()->getOnlinePlayers();
                foreach ($players as $playerass) {
                    //壊した人の名前§fがチーム名
                    $playerass->sendPopUp("§e".$name."が".$teamname."のコアを破壊しています！ \n残りHP TeamA:".$this->teamcore[1]." TeamB:".$this->teamcore[2]);
                }
                if(isset($ok)){
                    unset($ok);
                    $this->getServer()->broadcastMessage(TextFormat::RED ."[PVP]".$teamname."チームが勝ちました。";

                    ///プレイヤーtpと動けなくする
                    foreach ($players as $playerass) {
                        $playerass->teleport(Server::getInstance()->getDefaultLevel()->getSpawnLocation());
                    }


					得点リセット
                }
            }
        }
    }



    public function onPlayerDeath(PlayerDeathEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        unset($this->joinedpvp[$name]);
    }

    public function onPlayerQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        unset($this->joinedpvp[$name]);
    }

    public function getTeam($name)
    {
        if(isset($this->team[1][$name])){
            return "a";
        }elseif(isset($this->team[2][$name])){
            return "b";
        }else {
            return "f";
        }

    }


    ////共食い
    public function optionbow(EntityDamageEvent $event){
        if($event instanceof EntityDamageEvent){
            if($event instanceof EntityDamageByEntityEvent) {
                if($event->getDamager() instanceof Player && $event->getEntity() instanceof Player) {
                    $sender = $event->getDamager();
                    $reciever = $event->getEntity();
                    unset($sname);
                    unset($rname);
                    $sname = $sender->getName();
                    $rname = $reciever->getName();
                    if(!isset($this->joinedpvp[$sname])){
                        $sender->sendPopUp(TextFormat::YELLOW."PVPに参加していないプレイヤーは攻撃できません。");
                        $event->setCancelled();
                    }
                    if(isset($this->team[1][$sname])and  isset($this->team[1][$rname])){$sender->sendPopUp(TextFormat::YELLOW."同チームのプレイヤーは攻撃できません。");
                        $event->setCancelled();
                    }
                    elseif(isset($this->team[2][$sname])and isset( $this->team[2][$rname])){$sender->sendPopUp(TextFormat::YELLOW."同チームのプレイヤーは攻撃できません。");
                        $event->setCancelled();
                    }
                }
            }
        }
    }

    /**
     * @param PlayerJoinEvent $event
     */
    public function Onjoin(PlayerJoinEvent $event){
        global $config2;
        $player = $event->getPlayer();
        if(!$config2->exists($player->getName())){
            $config2->set($player->getName(),array("kill"=>0,"level"=>1));
        }
        $this->setTitle($player);
    }

    public function death(PlayerDeathEvent $event){
        $player = $event->getPlayer();
        global $config2;
        $ev = $player->getLastDamageCause();
        if ($ev instanceof EntityDamageByEntityEvent) {
            //$event->setDeathMessage();
            $killer = $ev->getDamager();
            $killername = $killer->getName();
            if ($killer instanceof Player) {
                $lv = $config2->get($killername);
                $lv["kill"]++;
                if($lv["level"] !=0&$lv["level"]%100==0){
                    if($config2->get($killername)!=15) {
                        $lv["level"] = round($lv["kill"] / 100);
                    }
                }
                /* Levelプラグインで倒した時に
                 君は○○を倒した！
                 ○○PMをゲットした！
                 次のレベルまで残り○○キル*/
                $permoney =$config2->get("これは設定です");
                $money = $lv["level"]*$permoney["xレベルの時にいくらもらえるか"];
                $this->pocketmoney->grantMoney($killername,$money,true);
                $killer->sendMessage("君は".$player->getName()."を倒した！\n
                ".$money."PMをゲットした！\n
                次のレベルまで残り".($lv["kill"]-($lv["level"]*100))."キル\n
                ");
                $config2->set($killername,$lv);
                $this->setTitle($killer);
            }
        }
    }

    public function setTitle(Player $player){
        $name =$player->getName();
        global $config2;
        $lv = $config2->get($name)!=15?$config2->get($name):"§dMax";

        ///あとで
        $team =null;
        if(isset($this->team[1][$name])){
            $team ="<TeamA>";
        }elseif(isset($this->team[2][$name])) {
            $team ="<TeamB>";
        }
        $player->setDisplayName("<".$name."[".$lv['level']."Lv]".$team.">");
        $player->setNameTag("<".$name."[".$lv['level']."Lv]".$team.">");
    }
}