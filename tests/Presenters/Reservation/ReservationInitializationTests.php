<?php
/**
 * Copyright 2011-2015 Nick Korbel
 *
 * This file is part of Booked Scheduler.
 *
 * Booked Scheduler is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Booked Scheduler is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Booked Scheduler.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once(ROOT_DIR . 'Domain/Access/namespace.php');
require_once(ROOT_DIR . 'lib/Application/Reservation/namespace.php');
require_once(ROOT_DIR . 'Pages/Reservation/ReservationPage.php');
require_once(ROOT_DIR . 'Pages/Reservation/NewReservationPage.php');
require_once(ROOT_DIR . 'lib/Application/Reservation/NewReservationInitializer.php');

class ReservationInitializationTests extends TestBase
{
	/**
	 * @var IReservationComponentBinder|PHPUnit_Framework_MockObject_MockObject
	 */
	private $userBinder;

	/**
	 * @var IReservationComponentBinder|PHPUnit_Framework_MockObject_MockObject
	 */
	private $dateBinder;

	/**
	 * @var IReservationComponentBinder|PHPUnit_Framework_MockObject_MockObject
	 */
	private $resourceBinder;

	/**
	 * @var INewReservationPage|PHPUnit_Framework_MockObject_MockObject
	 */
	private $page;

	/**
	 * @var NewReservationInitializer|PHPUnit_Framework_MockObject_MockObject
	 */
	private $initializer;

	/**
	 * @var FakeScheduleRepository
	 */
	private $scheduleRepository;

	/**
	 * @var IResourceRepository
	 */
	private $resourceRepository;

	public function setup()
	{
		parent::setup();

		$this->userBinder = $this->getMock('IReservationComponentBinder');
		$this->dateBinder = $this->getMock('IReservationComponentBinder');
		$this->resourceBinder = $this->getMock('IReservationComponentBinder');
		$this->page = $this->getMock('INewReservationPage');

		$this->scheduleRepository = new FakeScheduleRepository();
		$this->resourceRepository = $this->getMock('IResourceRepository');
		$this->initializer = new NewReservationInitializer($this->page,
														   $this->userBinder,
														   $this->dateBinder,
														   $this->resourceBinder,
														   $this->fakeUser,
														   $this->scheduleRepository,
														   $this->resourceRepository);
	}

	public function teardown()
	{
		parent::teardown();
	}

	public function testInitializesReservationData()
	{
		$scheduleId = 1;

		$this->page->expects($this->once())
				   ->method('GetRequestedScheduleId')
				   ->will($this->returnValue($scheduleId));

		$this->page->expects($this->once())
				   ->method('SetScheduleId')
				   ->with($this->equalTo($scheduleId));

		$this->userBinder->expects($this->once())
						 ->method('Bind')
						 ->with($this->equalTo($this->initializer));

		$this->dateBinder->expects($this->once())
						 ->method('Bind')
						 ->with($this->equalTo($this->initializer));

		$this->resourceBinder->expects($this->once())
							 ->method('Bind')
							 ->with($this->equalTo($this->initializer));

		$this->initializer->Initialize();
	}

	public function testBindsToClosestPeriod()
	{
		$page = $this->getMock('INewReservationPage');
		$binder = $this->getMock('IReservationComponentBinder');

		$timezone = $this->fakeUser->Timezone;

		$dateString = Date::Now()->AddDays(1)->SetTimeString('02:55:22')->Format('Y-m-d H:i:s');
		$endDateString = Date::Now()->AddDays(1)->SetTimeString('4:55:22')->Format('Y-m-d H:i:s');
		$dateInUserTimezone = Date::Parse($dateString, $timezone);

		$startDate = Date::Parse($dateString, $timezone);
		$endDate = Date::Parse($endDateString, $timezone);

		$expectedStartPeriod = new SchedulePeriod($dateInUserTimezone->SetTime(new Time(3, 30, 0)), $dateInUserTimezone->SetTime(new Time(4, 30, 0)));
		$expectedEndPeriod = new SchedulePeriod($dateInUserTimezone->SetTime(new Time(4, 30, 0)), $dateInUserTimezone->SetTime(new Time(7, 30, 0)));
		$periods = array(
				new SchedulePeriod($dateInUserTimezone->SetTime(new Time(1, 0, 0)), $dateInUserTimezone->SetTime(new Time(2, 0, 0))),
				new SchedulePeriod($dateInUserTimezone->SetTime(new Time(2, 0, 0)), $dateInUserTimezone->SetTime(new Time(3, 0, 0))),
				new NonSchedulePeriod($dateInUserTimezone->SetTime(new Time(3, 0, 0)), $dateInUserTimezone->SetTime(new Time(3, 30, 0))),
				$expectedStartPeriod,
				$expectedEndPeriod,
				new SchedulePeriod($dateInUserTimezone->SetTime(new Time(7, 30, 0)), $dateInUserTimezone->SetTime(new Time(17, 30, 0))),
				new SchedulePeriod($dateInUserTimezone->SetTime(new Time(17, 30, 0)), $dateInUserTimezone->SetTime(new Time(0, 0, 0))),
		);

		$page->expects($this->once())
			 ->method('SetSelectedStart')
			 ->with($this->equalTo($expectedStartPeriod), $this->equalTo($startDate));

		$page->expects($this->once())
			 ->method('SetSelectedEnd')
			 ->with($this->equalTo($expectedEndPeriod), $this->equalTo($endDate));

		$page->expects($this->once())
			 ->method('SetRepeatTerminationDate')
			 ->with($this->equalTo($endDate));

		$initializer = new NewReservationInitializer($page, $binder, $binder, $binder, $this->fakeUser, $this->scheduleRepository, $this->resourceRepository);
		$initializer->SetDates($startDate, $endDate, $periods, $periods);
	}

	public function testWhenNoScheduleIsPassed_UseDefaultScheduleId()
	{
		$id = $this->scheduleRepository->_DefaultScheduleId;

		$this->page->expects($this->once())
						   ->method('GetRequestedScheduleId')
						   ->will($this->returnValue(null));

		$this->page->expects($this->once())
						   ->method('SetScheduleId')
						   ->with($this->equalTo($id));

		$this->initializer->Initialize();
	}
}