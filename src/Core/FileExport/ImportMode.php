<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Sowapps\SoCore\Core\FileExport;

enum ImportMode: string {
	/**
	 * Ignore existing and add only new ones
	 */
	case Add     = 'add';
	
	/**
	 * Always create new rows (no dedupe). It's a naive operation, it does not check duplicates, so it could lead to SQL errors while importing.
	 * Recommended only if the file has only new rows
	 */
	case Append  = 'append';
	
	/**
	 * Remove previous then import all as new. It could be very naive to consider the imported rows will take the same ID as previously.
	 * Recommended only if none of the database entries is used as a foreign key
	 */
	case Replace = 'replace';
	
	/**
	 * Update existing and add new ones
	 */
	case Update  = 'update';
	
	public function label(): string
	{
		return match ($this) {
			self::Add => 'feature.import.mode.add',
			self::Append => 'feature.import.mode.append',
			self::Replace => 'feature.import.mode.replace',
			self::Update => 'feature.import.mode.update',
		};
	}
}
