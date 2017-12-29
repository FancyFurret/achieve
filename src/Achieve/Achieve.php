<?php

namespace Achieve;

use pocketmine\Achievement;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\OfflinePlayer;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class Achieve extends PluginBase {

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
		switch($command->getName()) {
            case "achieve":
                if (count($args) != 0) {
                    return false;
                }

                $this->listAchievements($sender, $sender->getName());
                return true;

            case "achievep":
                if (count($args) != 1) {
                    return false;
                }

                $this->listAchievements($sender, $args[0]);
                return true;
		}

		return false;
	}

	public function listAchievements(CommandSender $sender, string $playername) {

        // Make sure this player has played on this server
        if (!$this->getServer()->getOfflinePlayer($playername)->hasPlayedBefore()) {
            $sender->sendMessage(TextFormat::RED . "Cannot find player " . $playername);
            return;
        }

        // Make get the players NBT data
        $playerData = $this->getServer()->getOfflinePlayerData($playername);
        if (!($playerData instanceof CompoundTag)) {
            $sender->sendMessage(TextFormat::RED . "Cannot find NBT data for player " . $playername);
            return;
        }

        $achievements = $playerData->getCompoundTag("Achievements");

        $message = sprintf(
            "%d/%d Achievements Unlocked\n" .
                   TextFormat::AQUA . TextFormat::BOLD . "---------------------------" . TextFormat::RESET . "\n",
            $this->getAmountOfUnlockedAchievements($achievements),
            $this->getTotalAchievements());

        foreach (Achievement::$list as $a_id => $achievement) {
            if ($achievements[$a_id] == 1) {
                $message .= TextFormat::GREEN . TextFormat::BOLD . $achievement["name"] . TextFormat::RESET . "\n";
            }
            else {
                $requirements = $this->getUnmetRequirements($a_id, $achievements);
                if ($requirements == "") {
                    $message .= TextFormat::WHITE;
                }
                else {
                    $message .= TextFormat::GRAY;
                }
                $message .= $achievement["name"] . " " . $requirements . TextFormat::RESET . "\n";
            }

        }

        $sender->sendMessage($message);
	}

	private function getAmountOfUnlockedAchievements(CompoundTag $achievements) : int {
        $amount = 0;

        foreach ($achievements as $a_name => $a_gotten) {
            if ($a_gotten->getValue() == 1) {
                $amount++;
            }
        }

        return $amount;
    }

    private function getTotalAchievements() : int {
        return count(Achievement::$list);
    }

    private function getUnmetRequirements(string $achievement, CompoundTag $achievements) : string {
        $requirements = "";

        foreach (Achievement::$list[$achievement]["requires"] as $requirement) {
            if (!$achievements->hasTag($requirement) || $achievements[$requirement] == 0) {
                if ($requirements != "")
                    $achievements .= ", ";
                $requirements .= "\"" . Achievement::$list[$requirement]["name"] . "\"";
            }
        }

        if ($requirements != "") {
            $requirements = "(Requires: " . $requirements;
            $requirements .= ")";
        }

        return $requirements;
    }
}
