<?php

namespace DarkWav\SAC;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\Plugin;
use pocketmine\plugin\PluginLoader;
use DarkWav\SAC\Observer;
use DarkWav\SAC\SACTick;

class SAC extends PluginBase
{
  public $Config;
  public $Logger;
  public $PlayerObservers = array();
  public $PlayersToKick   = array();

  public function onEnable()
  {
    $this->getServer()->getScheduler()->scheduleRepeatingTask(new SACTick($this), 1);
    @mkdir($this->getDataFolder());
    @mkdir("Players/", 0777, true);
    $this->saveDefaultConfig();
  
    $Config = $this->getConfig();
    $Logger = $this->getServer()->getLogger();
    $Server = $this->getServer();
    
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    $Logger->info(TextFormat::BLUE . "[GMCPE WatchDog] > ShadowAntiCheat Activated"            );
    $Logger->info(TextFormat::BLUE . "[GMCPE WatchDog] > ShadowAntiCheat v3.1.0 [Shadow]");
  
    if($Config->get("ForceOP"    )) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiForceOP"    );
    if($Config->get("NoClip"     )) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiNoClip"     );
    if($Config->get("Fly"        )) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiFly"        );
    if($Config->get("Glide"      )) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiGlide"      );
    if($Config->get("KillAura"   )) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiKillAura"   );
    if($Config->get("InstantKill")) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiInstantKill");
    if($Config->get("Reach"      )) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiReach"      );
    if($Config->get("Speed"      )) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiSpeed"      );
    if($Config->get("Regen"      )) $Logger->info(TextFormat::BLUE."[SAC] > Enabling AntiRegen"      );

    if($Config->get("Plugin-Version") !== "3.1.0")
    {
      $Logger->emergency(TextFormat::BLUE."[GMCPE WatchDog] > Your Config is incompatible with this plugin version, please update immediately!");
      $Server->shutdown();
    }

    if($Config->get("Config-Version") !== "3.5.0")
    {
      $Logger->warning(TextFormat::BLUE."[GMCPE WatchDog] > Your Config is out of date!");
    }
    
    foreach($Server->getOnlinePlayers() as $player)
    {
      $hash     = spl_object_hash($player);
      $name     = $player->getName();
      $oldhash  = null;
      $observer = null;
      
      foreach ($this->PlayerObservers as $key=>$obs)
      {
        if ($obs->PlayerName == $name)
        {
          $oldhash  = $key;
          $observer = $obs;
          $observer->Player = $player;
        }
      }
      if ($oldhash != null)
      {
        unset($this->PlayerObservers[$oldhash]);
        $this->PlayerObservers[$hash] = $observer;
        $this->PlayerObservers[$hash]->PlayerRejoin();
      }  
      else
      {
        $observer = new Observer($player, $this);
        $this->PlayerObservers[$hash] = $observer;
        $this->PlayerObservers[$hash]->PlayerJoin();      
      }      
    }  
  }

  public function onDisable()
  {
    $Logger = $this->getServer()->getLogger();
    $Server = $this->getServer();

    $Logger->info(TextFormat::BLUE."[GMCPE WatchDog] > You are no longer protected from cheats!");
    $Logger->info(TextFormat::BLUE."[GMCPE WatchDog] > ShadowAntiCheat Deactivated");
    $Server->enablePlugin($this);
  }
    
  public function onCommand(CommandSender $sender, Command $cmd, $label, array $args)
  {
    $Logger = $this->getServer()->getLogger();
    if ($this->getConfig()->get("ForceOP"))
    {
      if ($sender->isOp())
      {
        if (!$sender->hasPermission($this->getConfig()->get("ForceOP-Permission")))
        {
          if ($sender instanceof Player)
          {
            $sname = $sender->getName();
	    $message  = "[GMCPE WatchDog] > $sname used ForceOP!";
            $this->NotifyAdmins($message);
            $sender->getPlayer()->kick(TextFormat::BLUE."[GMCPE WatchDog] > ForceOP detected!");
          }
        }
      }
    }
	if(strtolower($cmd->getName()) === "report"){
        	if(!(isset($args[0]))){
          		$sender->sendMessage(TF::RED . "Error: Not Enough Parameters. Usage: /report <player>");
          		return true;
        	}else{
         	 $sender_name = $sender->getName();
         	 $sender_display_name = $sender->getDisplayName();
         	 $name = $args[0];
         	 $player = $this->getServer()->getPlayer($name);
         	 if($player === null){
         	   foreach($this->getServer()->getOnlinePlayers() as $p){
          	    if($p->hasPermission("gmcpe.staff")){
              		  $p->sendMessage(TF::YELLOW . $sender_name . " reported " . $name . " for using hacks and/or mods!");
             		}
            	}
            	$sender->sendMessage(TF::GREEN . "The Report has Been Sent to All Online Staffs.");
            	return true;
          }else{
            foreach($this->getServer()->getOnlinePlayers() as $p){
              	if($p->hasPermission("gmcpe.staff")){
                	$p->sendMessage(TF::YELLOW . $sender_name . " reported " . $name . " for using hacks and/or mods!");
             	 }
            }
            $player->sendMessage(TF::YELLOW . $sender_name . " has reported you for using hacks and/or mods!");
            $sender->sendMessage(TF::GREEN . "The Report has Been Sent to All Online Staffs.");
            return true;
          }
        }
      }
    }
    if(strtolower($cmd->getName()) === "warn") {
        if(!(isset($args[0]) and isset($args[1]))) {
          $sender->sendMessage(TF::RED . "Error: not enough args. Usage: /warn <player> <reason>");
          return true;
        } else {
          $sender_name = $sender->getName();
          $name = $args[0];
          $player = $this->getServer()->getPlayer($name);
          if($player->isOp()){
            $sender->sendMessage(TF::RED . $player->getName() . " has the OP status and thus, cannot be warned.");
            return;
          }
          if($player === null) {
            $sender->sendMessage(TF::RED . "Player " . $name . " could not be found.");
            return true;
          } else {
            unset($args[0]);
            $player_name = $player->getName();
            if(!(file_exists($this->dataPath() . "Players/" . strtolower($player_name) . ".txt"))) {
              touch($this->dataPath() . "Players/" . strtolower($player_name) . ".txt");
              file_put_contents($this->dataPath() . "Players/" . strtolower($player_name) . ".txt", "0");
            }
            $reason = implode(" ", $args);
            $file = file_get_contents($this->dataPath() . "Players/" . strtolower($player_name) . ".txt");
            if($file >= "3") {
              $string = "action_after_three_warns: ";
              $action = substr(strstr(file_get_contents($this->dataPath() . "config.yml"), $string), strlen($string));
                if($player->isOP()){
                  return;
                }
                $player->setBanned(true);
                $sender->sendMessage(TF::GREEN . $player_name . " was banned for being warned 3+ times.");
                return true;
            } else {
              $player->sendMessage(TF::YELLOW . "You have been warned by " . $sender_name . " for " . $reason);
              $this->getServer()->broadcastMessage(TF::YELLOW . $player_name . " was warned by " . $sender_name . " for " . $reason);
              $file = file_get_contents($this->dataPath() . "Players/" . strtolower($player_name) . ".txt");
              file_put_contents($this->dataPath() . "Players/" . strtolower($player_name) . ".txt", $file + 1);
              $sender->sendMessage(TF::GREEN . "Warned " . $player_name . ", and added +1 warns to their file.");
              return true;
            }
          }
        }
      }
      if(strtolower($cmd->getName()) === "warns") {
        if(!(isset($args[0]))) {
          $sender->sendMessage(TF::RED . "Error: not enough args. Usage: /warns <player>");
          return true;
        } else {
          $name = $args[0];
          $player = $this->getServer()->getPlayer($name);
          if($player === null) {
            $sender->sendMessage(TF::RED . "Player " . $name . " could not be found.");
            return true;
          } else {
            $player_name = $player->getName();
            if(!(file_exists($this->dataPath() . "Players/" . strtolower($player_name) . ".txt"))) {
              $sender->sendMessage(TF::RED . $player_name . " has no warns.");
              return true;
            } else {
              $player_warns = file_get_contents($this->dataPath() . "Players/" . strtolower($player_name) . ".txt");
              $sender->sendMessage(TF::GREEN . "Player " . $player_name . " has " . $player_warns . " warns.");
              return true;
            }
          }
        }
      }
    }
  }
  
  public function NotifyAdmins($message)
  {
    if($this->getConfig()->get("Verbose"))
    {
      foreach ($this->PlayerObservers as $observer)
      {
        $player = $observer->Player;
        if ($player != null and $player->hasPermission("gmcpe.staff"))
        {
          $player->sendMessage(TextFormat::BLUE . $message);
        }
      }
    }  
  }  
  
}

//////////////////////////////////////////////////////
//                                                  //
//     SAC by DarkWav.                              //
//     Distributed under the AntiCheat License.     //
//     Do not redistribute in modyfied form!        //
//     All rights reserved.                         //
//                                                  //
//////////////////////////////////////////////////////
