<?php

namespace DC\Widgets;

use SV\Utils\InstallerSoftRequire;
use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

	// ################################ INSTALLATION ####################
    public function installStep1()
    {
	    $sm = $this->schemaManager();

	    foreach ($this->getTables() AS $tableName => $closure)
	    {
		    $sm->createTable($tableName, $closure);
	    }
    }

	public function installStep2()
	{
		$sm = $this->schemaManager();

		$sm->alterTable('xf_thread', function(Alter $table)
		{
			$table->changeColumn('widgetPromoted', 'text')
				->nullable()
				->comment('List comma separated widget ids which thread promoted to.')
			;
			$table->addColumn('widgetPromoteDate', 'int')->setDefault(0);
		});
	}

	// ############################################ UPGRADE #######################################

    public function upgrade1010031Step1()
    {
        $sm = $this->schemaManager();

        $sm->alterTable('xf_thread', function(Alter $table)
        {
            $table->addColumn('widgetPromoted', 'tinyint', 1)->setDefault(0);
        });
    }

    public function upgrade1010032Step1()
    {
        $sm = $this->schemaManager();

        $sm->alterTable('xf_thread', function(Alter $table)
        {
            $table->addColumn('widgetPromoteDate', 'int')->setDefault(0);
        });
    }

	public function upgrade1010570Step1()
	{
		$this->installStep1();

		$sm = $this->schemaManager();
		$sm->alterTable('xf_thread', function(Alter $table) {
			$table->changeColumn('widgetPromoted', 'text')
				->nullable()
				->setDefault(null)
				->comment('List comma separated widget ids which thread promoted to.')
			;
		});

		$db = $this->db();

		// Update all promoted threads to be 'all' (promoted to all widgets)
		$db->update(
			'xf_thread',
			[
				'widgetPromoted' => 'all'
			],
			'widgetPromoted IS NOT NULL AND widgetPromoted = ?',
			[1]
		);

		// Update all non-promoted threads to be null
		$db->update(
			'xf_thread',
			[
				'widgetPromoted' => null
			],
			'widgetPromoted IS NOT NULL AND widgetPromoted = ?',
			[0]
		);
	}

	// ############################################ UNINSTALL #########################

    public function uninstallStep1()
    {
	    $sm = $this->schemaManager();

	    foreach (array_keys($this->getTables()) AS $tableName)
	    {
		    $sm->dropTable($tableName);
	    }
    }

	public function uninstallStep2()
	{
		$sm = $this->schemaManager();

		$sm->alterTable('xf_thread', function(Alter $table) {
			$table->dropColumns([
				'widgetPromoted',
				'widgetPromoteDate'
			]);
		});
	}

	// ############################# TABLE / DATA DEFINITIONS ##############################

	protected function getTables()
	{
		$tables = [];

		$tables['xf_dcWidgets_widget_action_log'] = function(Create $table)
		{
			$table->checkExists(true);

			$table->addColumn('log_id', 'int')->nullable()->autoIncrement();
			$table->addColumn('user_id', 'int');
			$table->addColumn('username', 'varchar', 50);
			$table->addColumn('thread_id', 'int');
			$table->addColumn('thread_title', 'varchar', 250);
			$table->addColumn('thread_username', 'varchar', 50);
			$table->addColumn('action', 'enum')->values(['promote', 'edit_promote', 'unpromote', 'create_schedule', 'edit_schedule', 'delete_schedule'])->setDefault('promote');
			$table->addColumn('action_date', 'int');

			$table->addKey('user_id');
			$table->addKey('thread_id');
		};

		$tables['xf_dcWidgets_widget_promote_schedule'] = function(Create $table)
		{
			$table->checkExists(true);

			$table->addColumn('thread_id', 'int');
			$table->addColumn('from_date', 'int');
			$table->addColumn('to_date', 'int');
			$table->addColumn('target_widget_ids', 'text');
			$table->addColumn('is_started', 'tinyint', 1)->setDefault(0);

			$table->addPrimaryKey('thread_id');
		};

		return $tables;
	}
}