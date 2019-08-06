<?php

namespace Localizationteam\Localizer\Handler;

use Exception;
use Localizationteam\Localizer\Constants;

/**
 * ErrorResetter resets status in Localizer cart to status before error occured so that this can rerun.
 *
 * @author      Peter Russ<peter.russ@4many.net>, Jo Hasenau<jh@cybercraft.de>
 * @package     TYPO3
 * @subpackage  localizer
 *
 */
class ErrorResetter extends AbstractHandler
{
    /**
     * @param $id
     * @throws Exception
     */
    public function init($id = 1)
    {
        $where = 'deleted = 0 AND hidden = 0 AND  status = ' . Constants::STATUS_CART_ERROR .
            ' AND previous_status <>""' .
            ' AND processid = ""';

        $this->setAcquireWhere($where);
        parent::init($id);
    }

    public function run()
    {
        if ($this->canRun() === true) {
            $query = 'UPDATE ' . Constants::TABLE_EXPORTDATA_MM .
                ' SET status = previous_status, previous_status = 0, last_error = "" WHERE previous_status <> ""' .
                ' AND processid = "' . $this->getProcessId() . '"';
            $this->getDatabaseConnection()->sql_query($query);
        }
    }

    /**
     * @param int $time
     * @return void
     */
    function finish($time)
    {
        // nothing to do here
    }
}