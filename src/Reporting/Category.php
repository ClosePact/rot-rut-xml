<?php

declare(strict_types=1);

namespace Closepact\RotRutXml\Reporting;

/**
 * Where a finding comes from. The categories are kept apart on purpose: an XSD
 * error, a rule from the law or Skatteverket's import rules, a format-transition
 * reminder and an optional house policy are different kinds of statements.
 */
enum Category: string
{
    /** Generator input (JSON or array) that is missing, mistyped or out of range. */
    case Input = 'input';

    /** Not well-formed, or refused for safety (DOCTYPE, size, depth). */
    case Xml = 'xml';

    /** Unknown, unsupported or unexpected format version. */
    case Version = 'version';

    /** Skatteverket's XSD for the version. */
    case Schema = 'schema';

    /** Built-in structural checks, used only when the XSD could not be run. */
    case Structure = 'structure';

    /** A rule stated by a primary source (law, Skatteverket's import rules, schema documentation). */
    case Rule = 'rule';

    /** Format changes over time (1.1 from 2027-01-01, the 2027 answers). */
    case Transition = 'transition';

    /** Optional house checks with no rule behind them; off unless asked for. */
    case Policy = 'policy';
}
