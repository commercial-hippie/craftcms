<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\debug;

use Craft;
use craft\base\FsInterface;
use craft\helpers\App;
use craft\web\View;
use yii\base\InvalidConfigException;

/**
 * The Yii Debug Module provides the debug toolbar and debugger
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Module extends \yii\debug\Module
{
    public $controllerNamespace = 'craft\debug\controllers';

    /**
     * @var FsInterface|null The filesystem that debug cache files should be stored in.
     * @since 4.0.0
     */
    public ?FsInterface $fs = null;

    /**
     * @inheritDoc
     */
    public function bootstrap($app): void
    {
        parent::bootstrap($app);

        $this->logTarget = $app->getLog()->targets['debug'] = new LogTarget($this);
    }

    /**
     * @inheritdoc
     */
    public function renderToolbar($event): void
    {
        if (!$this->checkAccess() || Craft::$app->getRequest()->getIsAjax()) {
            return;
        }

        /** @var View $view */
        $view = $event->sender;
        echo $this->getToolbarHtml();

        echo '<style>' . $view->renderPhpFile($this->getBasePath() . '/assets/css/toolbar.css') . '</style>';
        echo '<script>' . $view->renderPhpFile($this->getBasePath() . '/assets/js/toolbar.js') . '</script>';
    }
}
