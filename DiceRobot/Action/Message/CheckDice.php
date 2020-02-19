<?php
namespace DiceRobot\Action\Message;

use DiceRobot\Base\AbstractAction;
use DiceRobot\Base\API;
use DiceRobot\Base\CharacterCard;
use DiceRobot\Base\CheckDiceRule;
use DiceRobot\Base\Customization;
use DiceRobot\Base\DiceOperation;
use DiceRobot\Base\RobotSettings;
use DiceRobot\Exception\OrderErrorException;

/**
 * Class CheckDice
 *
 * Action class of order ".ra". Roll a check dice to investigator's attribute or skill.
 */
final class CheckDice extends AbstractAction
{
    /** @noinspection PhpUnhandledExceptionInspection */
    public function __invoke(): void
    {
        $order = preg_replace("/^\.ra[\s]*/i", "", $this->message, 1);

        if (!preg_match("/^(h[\s]*)?([bp]([\s]*[1-9][0-9]*)?[\s]+)?([\x{4e00}-\x{9fa5}a-z]+|[1-9][0-9]*)([\s]*[+-][1-9][0-9]*)*([\s]*#[1-9]?)?$/ui",
            $order))
            throw new OrderErrorException;

        preg_match("/^(h[\s]*)?([bp]([\s]*[1-9][0-9]*)?[\s]+)?/", $order, $optionalOrder);
        $optionalOrder = $optionalOrder[0];
        $order = preg_replace("/^(h[\s]*)?([bp]([\s]*[1-9][0-9]*)?[\s]+)?/",
            "", $order, 1);

        if (preg_match("/^[1-9][0-9]*/", $order, $checkValue))
            $checkValue = intval($checkValue[0]);
        elseif (preg_match("/^[\x{4e00}-\x{9fa5}a-z]+/ui", $order, $checkValueName))
        {
            $checkValueName = strtoupper($checkValueName[0]);
            $cardId = RobotSettings::getCharacterCard($this->userId);

            if (is_null($cardId))
            {
                $this->reply = Customization::getCustomReply("checkDiceCharacterCardNotBound");
                return;
            }

            $characterCard = new CharacterCard($cardId);
            $characterCard->load();
            $checkValue = $characterCard->get($checkValueName);

            if (is_null($checkValue))
            {
                $this->reply = Customization::getCustomReply("checkDiceValueNotFound");
                return;
            }
        }

        $order = preg_replace("/^([\x{4e00}-\x{9fa5}a-z]+|[1-9][0-9]*)[\s]*/ui",
            "", $order, 1);
        preg_match("/^([+-][1-9][0-9]*)*/", $order, $additional);
        $additional = $additional[0];
        $order = preg_replace("/^([+-][1-9][0-9]*)*[\s]*/",
            "", $order, 1);
        preg_match("/[1-9]$/", $order, $repeat);
        $repeat = isset($repeat[0]) ? intval($repeat[0]) : 1;

        if ($checkValue < 1 || $checkValue > Customization::getCustomSetting("maxAttribute"))
        {
            $this->reply = Customization::getCustomReply("checkDiceValueOverRange");
            return;
        }
        elseif ($repeat < 1 || $repeat > Customization::getCustomSetting("maxRepeatTimes"))
        {
            $this->reply = Customization::getCustomReply("checkDiceValueOverRange");
            return;
        }

        $this->reply = Customization::getCustomReply("checkDiceResultHeading",
            $this->userNickname, $repeat, $checkValueName ?? "") . ($repeat > 1 ? "\n" : "");
        $hCheckReply = Customization::getCustomReply("checkDicePrivateCheck",
            $this->userNickname, $repeat);

        while ($repeat--)
        {
            $diceOperation = new DiceOperation(trim($optionalOrder . " D100"));

            if ($diceOperation->success != 0)
            {
                $this->reply = Customization::getCustomReply("checkDiceBPNumberOverRange");
                return;
            }

            $evalString = "return " . $diceOperation->rollResult . $additional . ";";
            $checkResult = eval($evalString);
            $checkResult = $checkResult < 1 ? 1 : $checkResult;
            $checkResult = $checkResult > 100 ? 100 : $checkResult;

            if (is_null($diceOperation->bpType))
            {
                // Normal dice
                $rollingResultString = $diceOperation->expression . $additional . "=" .
                    $diceOperation->rollResult . $additional . ($additional == "" ? "" : "=" . $checkResult);
            }
            else
                // B/P dice
                $rollingResultString = $diceOperation->bpType . $diceOperation->bpDiceNumber . $additional . "=" .
                    $diceOperation->toResultExpression() . "[" .
                    Customization::getCustomReply("_BPDiceWording")[$diceOperation->bpType] . ":" .
                    join(" ", $diceOperation->bpResult) . "]" . $additional . "=" .
                    $diceOperation->rollResult . $additional . ($additional == "" ? "" : "=" . $checkResult);

            $this->reply .= Customization::getCustomReply("checkDiceResult",
                    $rollingResultString, $checkValue, $this->checkDiceLevel($checkResult, $checkValue)) . "\n";
        }

        $this->reply = trim($this->reply);

        /** @noinspection PhpUndefinedVariableInspection */
        if ($diceOperation->vType === "H")
        {
            if ($this->chatType == "private")
            {
                $this->reply = Customization::getCustomReply("checkDicePrivateChatPrivateCheck");
                return;
            }
            elseif ($this->chatType == "group")
                $privateReply = Customization::getCustomReply("checkDicePrivateCheckFromGroup",
                    API::getGroupInfo($this->chatId)["data"]["group_name"], $this->chatId);
            else
                $privateReply = Customization::getCustomReply("checkDicePrivateCheckFromDiscuss",
                    $this->chatId);

            $privateReply .= $this->reply;
            API::sendPrivateMessageAsync($this->userId, $privateReply);

            $this->reply = $hCheckReply;
        }
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    private function checkDiceLevel(int $result, int $value)
    {
        $checkRules = Customization::getCustomFile(COC_CHECK_DICE_RULE_PATH)["rules"];
        $ruleIndex = RobotSettings::getSetting("cocCheckRule") ?? 0;
        $checkLevel = (new CheckDiceRule($checkRules, $ruleIndex))->getCheckLevel($result, $value);

        return Customization::getCustomReply("_checkLevel")[$checkLevel];
    }
}
