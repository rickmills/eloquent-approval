<?php

namespace Mtvs\EloquentApproval\Tests;

use Mtvs\EloquentApproval\ApprovalStatuses;
use Mtvs\EloquentApproval\Tests\Models\Entity;
use Mtvs\EloquentApproval\Tests\Models\EntityWithCustomApprovalStatusColumn;

class ApprovableTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_default_for_approval_status_column()
    {
        $entity = new Entity();

        $this->assertEquals('approval_status', $entity->getApprovalStatusColumn());
    }

    /**
     * @test
     */
    public function it_can_detect_custom_approval_status_column()
    {
        $entity = new EntityWithCustomApprovalStatusColumn();

        $this->assertEquals(
            EntityWithCustomApprovalStatusColumn::APPROVAL_STATUS,
            $entity->getApprovalStatusColumn()
        );
    }

    /**
     * @test
     */
    public function it_can_approve_the_entity()
    {
        $entity = factory(Entity::class)->create();

        $entity->approve();

        $this->assertEquals(ApprovalStatuses::APPROVED, $entity->approval_status);

        $this->assertDatabaseHas('entities', [
            'id' => $entity->id,
            'approval_status' => ApprovalStatuses::APPROVED
        ]);
    }

    /**
     * @test
     */
    public function it_can_reject_the_entity()
    {
        $entity = factory(Entity::class)->create();

        $entity->reject();

        $this->assertEquals(ApprovalStatuses::REJECTED, $entity->approval_status);

        $this->assertDatabaseHas('entities', [
            'id' => $entity->id,
            'approval_status' => ApprovalStatuses::REJECTED
        ]);
    }

    /**
     * @test
     */
    public function it_can_suspend_the_entity()
    {
        $entity = factory(Entity::class)->create([
            'approval_status' => ApprovalStatuses::APPROVED
        ]);

        $entity->suspend();

        $this->assertEquals(ApprovalStatuses::PENDING, $entity->approval_status);

        $this->assertDatabaseHas('entities', [
            'id' => $entity->id,
            'approval_status' => ApprovalStatuses::PENDING
        ]);
    }

    /**
     * @test
     */
    public function it_refreshes_the_entity_approval_at_on_status_update()
    {
        $entities = factory(Entity::class, 3)->create();

        $time = (new Entity())->freshTimestamp();

        $entities[0]->approve();
        $entities[1]->reject();
        $entities[2]->suspend();

        foreach ($entities as $entity) {
            $this->assertEquals($time->timestamp, $entity->approval_at->timestamp);

            $this->assertDatabaseHas('entities', [
                'id' => $entity->id,
                'approval_at' => $entity->fromDateTime($time)
            ]);
        }
    }

    /**
     * @test
     */
    public function it_refreshes_the_entity_updated_at_on_status_update()
    {
        $time = (new Entity())->freshTimestamp();

        $entities = factory(Entity::class, 3)->create([
            'updated_at' => (New Entity())->fromDateTime($time->copy()->subHour())
        ]);

        $entities[0]->approve();
        $entities[1]->reject();
        $entities[2]->suspend();

        foreach ($entities as $entity) {
            $this->assertEquals($time->timestamp, $entity->updated_at->timestamp);

            $this->assertDatabaseHas('entities', [
                'id' => $entity->id,
                'updated_at' => $entity->fromDateTime($time)
            ]);
        }
    }

    /**
     * @test
     */
    public function it_returns_true_when_updates_status()
    {
        $entity = factory(Entity::class)->create();

        $this->assertTrue($entity->approve());
        $this->assertTrue($entity->reject());
        $this->assertTrue($entity->suspend());
    }

    /**
     * @test
     */
    public function it_refuses_to_update_status_of_not_exists()
    {
        $entities = factory(Entity::class, 3)->make();

        $this->assertNull($entities[0]->approve());
        $this->assertNull($entities[1]->reject());
        $this->assertNull($entities[2]->suspend());

        foreach ($entities as $entity) {
            $this->assertNull($entity->approval_at);
        }
    }
}
