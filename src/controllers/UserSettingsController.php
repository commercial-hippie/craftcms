<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\controllers;

use Craft;
use craft\models\UserGroup;
use craft\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * The UserSettingsController class is a controller that handles various user group and user settings related tasks such as
 * creating, editing and deleting user groups and saving Craft user settings.
 * Note that all actions in this controller require administrator access in order to execute.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class UserSettingsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        // All user settings actions require an admin
        $this->requireAdmin();

        if ($action->id !== 'save-user-settings') {
            Craft::$app->requireEdition(Craft::Pro);
        }

        return parent::beforeAction($action);
    }

    /**
     * Saves a user group.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested user group cannot be found
     */
    public function actionSaveGroup()
    {
        $this->requirePostRequest();

        $groupId = $this->request->getBodyParam('groupId');

        if ($groupId) {
            $group = Craft::$app->getUserGroups()->getGroupById($groupId);

            if (!$group) {
                throw new NotFoundHttpException('User group not found');
            }
        } else {
            $group = new UserGroup();
        }

        $group->name = $this->request->getBodyParam('name');
        $group->handle = $this->request->getBodyParam('handle');
        $group->description = $this->request->getBodyParam('description');

        // Did it save?
        if (!Craft::$app->getUserGroups()->saveGroup($group)) {
            $this->setFailFlash(Craft::t('app', 'Couldn’t save group.'));

            // Send the group back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'group' => $group
            ]);

            return null;
        }

        // Save the new permissions
        $permissions = $this->request->getBodyParam('permissions', []);

        // See if there are any new permissions in here
        if ($groupId && is_array($permissions)) {
            foreach ($permissions as $permission) {
                if (!$group->can($permission)) {
                    // Yep. This will require an elevated session
                    $this->requireElevatedSession();
                    break;
                }
            }
        }

        Craft::$app->getUserPermissions()->saveGroupPermissions($group->id, $permissions);

        $this->setSuccessFlash(Craft::t('app', 'Group saved.'));
        return $this->redirectToPostedUrl();
    }

    /**
     * Deletes a user group.
     *
     * @return Response
     */
    public function actionDeleteGroup(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $groupId = $this->request->getRequiredBodyParam('id');

        Craft::$app->getUserGroups()->deleteGroupById($groupId);

        return $this->asJson(['success' => true]);
    }

    /**
     * Saves the system user settings.
     *
     * @return Response|null
     */
    public function actionSaveUserSettings()
    {
        $this->requirePostRequest();
        $projectConfig = Craft::$app->getProjectConfig();
        $settings = $projectConfig->get('users') ?? [];

        $settings['photoVolumeUid'] = $this->request->getBodyParam('photoVolumeUid') ?: null;
        $settings['photoSubpath'] = $this->request->getBodyParam('photoSubpath');

        if (Craft::$app->getEdition() === Craft::Pro) {
            $settings['requireEmailVerification'] = (bool)$this->request->getBodyParam('requireEmailVerification');
            $settings['allowPublicRegistration'] = (bool)$this->request->getBodyParam('allowPublicRegistration');
            $settings['suspendByDefault'] = (bool)$this->request->getBodyParam('suspendByDefault');
            $settings['defaultGroup'] = $this->request->getBodyParam('defaultGroup');
        }

        $projectConfig->set('users', $settings, 'Update user settings');

        $this->setSuccessFlash(Craft::t('app', 'User settings saved.'));
        return $this->redirectToPostedUrl();
    }
}
