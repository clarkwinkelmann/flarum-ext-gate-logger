<?php

namespace ClarkWinkelmann\GateLogger;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;

/**
 * Re-implementation of Flarum's Flarum\User\Access\Gate with log ability
 */
class Gate
{
    protected const EVALUATION_CRITERIA_PRIORITY = [
        AbstractPolicy::FORCE_DENY => false,
        AbstractPolicy::FORCE_ALLOW => true,
        AbstractPolicy::DENY => false,
        AbstractPolicy::ALLOW => true,
    ];

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * @var array
     */
    protected $policyClasses;

    /**
     * @var array
     */
    protected $policies;

    public function __construct(Container $container, LoggerInterface $log, array $policyClasses)
    {
        $this->container = $container;
        $this->log = $log;
        $this->policyClasses = $policyClasses;
    }

    public function allows(User $actor, string $ability, $model): bool
    {
        $log = 'Gate Logger ' . $ability . '(Actor: ' . ($actor->isGuest() ? '[Guest]' : $actor->username) . ', ' . ($model ? (is_string($model) ? $model . '::class' : (get_class($model) . ($model instanceof Model ? ': ' . $model->id : ''))) : '[GLOBAL]') . ')';

        $result = $this->allowsInternal($actor, $ability, $model, $log);

        $this->log->debug($log);

        return $result;
    }

    protected function allowsInternal(User $actor, string $ability, $model, string &$log): bool
    {
        $results = [];
        $appliedPolicies = [];

        if ($model) {
            $modelClasses = is_string($model) ? [$model] : array_merge(class_parents(($model)), [get_class($model)]);

            foreach ($modelClasses as $class) {
                $appliedPolicies = array_merge($appliedPolicies, $this->getPolicies($class));
            }
        } else {
            $appliedPolicies = $this->getPolicies(AbstractPolicy::GLOBAL);
        }

        foreach ($appliedPolicies as $policy) {
            $result = null;

            // We bypass AbstractPolicy::checkAbility to be able to tell which method was called
            if (method_exists($policy, $ability)) {
                $result = call_user_func_array([$policy, $ability], [$actor, $model]);

                $log .= "\n" . get_class($policy) . '@' . $ability . ': ' . $this->formatResult($result);
            }

            if (method_exists($policy, 'can')) {
                $log .= "\n" . get_class($policy) . '@can: ';

                if (is_null($result)) {
                    $result = call_user_func_array([$policy, 'can'], [$actor, $ability, $model]);

                    $log .= $this->formatResult($result);
                } else {
                    $log .= 'SKIPPED';
                }
            }

            if ($result === true) {
                $result = AbstractPolicy::ALLOW;
            } elseif ($result === false) {
                $result = AbstractPolicy::DENY;
            }

            $results[] = $result;
        }

        foreach (static::EVALUATION_CRITERIA_PRIORITY as $criteria => $decision) {
            if (in_array($criteria, $results, true)) {
                $log .= "\nDecision: " . ($decision ? 'ALLOW' : 'DENY') . " (Criteria Priority: $criteria)";

                return $decision;
            }
        }

        if ($actor->isAdmin()) {
            $log .= "\nDecision: ALLOW (Admin role)";

            return true;
        }

        if ($actor->hasPermission($ability)) {
            $log .= "\nDecision: ALLOW (Has permission $ability)";

            return true;
        }

        $log .= "\nDecision: DENY (Default)";

        return false;
    }

    /**
     * Get all policies for a given model and ability.
     */
    protected function getPolicies(string $model)
    {
        $compiledPolicies = Arr::get($this->policies, $model);
        if (is_null($compiledPolicies)) {
            $policyClasses = Arr::get($this->policyClasses, $model, []);
            $compiledPolicies = array_map(function ($policyClass) {
                return $this->container->make($policyClass);
            }, $policyClasses);
            Arr::set($this->policies, $model, $compiledPolicies);
        }

        return $compiledPolicies;
    }

    protected function formatResult($result): string
    {
        if (is_string($result)) {
            return $result;
        }

        if (is_null($result)) {
            return '[NULL]';
        }

        if ($result) {
            return '[TRUE] => ALLOW';
        }

        return '[FALSE] => DENY';
    }
}
