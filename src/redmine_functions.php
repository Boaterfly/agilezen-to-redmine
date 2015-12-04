<?php

namespace AgileZenToRedmine\Redmine;

use AgileZenToRedmine\Api\AgileZen\Project;

/**
 * Sanitize a string for use as a Redmine project identifier.
 *
 * @return string $str
 * @return string
 */
function identifier_from_agilezen_project(Project $project)
{
    $str = $project->name;

    if (!ctype_alpha((string) $str[0])) {
        throw new \InvalidArgumentException('Project name must start with a letter.');
    }

    return implode('', array_filter(
        str_split(strtolower($str)),
        function ($c) {
            return ctype_alnum((string) $c)
                || $c === '-'
                || $c === '_'
            ;
        }
    ));
}

/**
 * Create a description from a AgileZen project.
 *
 * @return string
 */
function description_from_agilezen_project(Project $project)
{
    return implode("\n", [
        $project->description,
        '',
        "Project #{$project->id} from AgileZen.",
        "Originally created at {$project->createTime} by {$project->owner->name}."
    ]);
}
