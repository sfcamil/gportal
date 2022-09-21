<?php

namespace Drupal\gepsis\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

class SubuserPermissions implements AccessInterface
{

    public function checkUserDeleteAccess(AccountInterface $account) {
        $roles = $account->getRoles();
        if (!in_array('student', $roles)) {
            return AccessResult::forbidden();
        }
        return AccessResult::allowed();
    }

    /**
     * A custom access check.
     *
     * @param \Drupal\Core\Session\AccountInterface $account
     *   Run access checks for this account.
     *
     * @return \Drupal\Core\Access\AccessResultInterface
     *   The access result.
     */
    public function checkUserEditAccess(AccountInterface $account) {
        // Check permissions and combine that with any custom access checking needed. Pass forward
        // parameters from the route and/or request as needed.
        return AccessResult::allowed();
        return ($account->hasPermission('do example things') && $this->someOtherCustomCondition()) ? AccessResult::allowed() : AccessResult::forbidden();
    }

}