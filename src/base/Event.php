<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\helpers\App;
use yii\base\Event as BaseEvent;

/**
 * ElementTrait implements the common methods and properties for element classes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class Event extends BaseEvent
{
    /**
     * Attaches an event handler to a class-level event, which will be triggered
     * at most one time.
     *
     * @param string $class The fully qualified class name to which the event handler needs to attach.
     * @param string $name The event name.
     * @param callable $handler The event handler.
     * @param mixed $data The data to be passed to the event handler when the event is triggered.
     * When the event handler is invoked, this data can be accessed via [[BaseEvent::data]].
     * @param bool $append Whether to append new event handler to the end of the existing
     * handler list. If `false`, the new handler will be inserted at the beginning of the existing
     * handler list.
     * @see on()
     */
    public static function once(
        string $class,
        string $name,
        callable $handler,
        mixed $data = null,
        bool $append = true,
    ): void {
        $triggered = false;
        static::on($class, $name, function(BaseEvent $event) use ($handler, &$triggered) {
            if (!$triggered) {
                $triggered = true;
                $handler($event);
            }
        }, $data, $append);
    }

    public function __construct($config = [])
    {
        // Call App::configure() rather than BaseYii::configure() (via BaseObject::__construct()),
        // in case \Yii isn't loaded yet. (Mainly an issue for GeneralConfig/DbConfig, if config/general.php
        // or config/db.php return an array.)
        // Note that inlining the foreach loop is no good, because then private/protected properties will be
        // set directly rather than going through __set().
        App::configure($this, $config);

        // Intentionally not passing $config along
        parent::__construct();
    }
}
