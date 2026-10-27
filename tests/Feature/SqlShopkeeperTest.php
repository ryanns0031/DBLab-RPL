<?php

namespace Tests\Feature;

use App\Models\SqlShopkeeperProgress;
use App\Models\User;
use App\Services\SqlShopkeeperGame;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SqlShopkeeperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function student(): User
    {
        return User::where('role', 'siswa')->firstOrFail();
    }

    public function test_shopkeeper_is_inside_the_student_mini_games_catalog(): void
    {
        $student = $this->student();
        $this->actingAs($student)
            ->get('/siswa/zona-santai/mini-games-sql')
            ->assertOk()
            ->assertSee('SQL Shopkeeper 3D')
            ->assertSee('Simulasi 3D')
            ->assertDontSee('Tebak Hasil Query')
            ->assertDontSee('Query Detective')
            ->assertDontSee('Database Escape Room');

        $this->get('/siswa/zona-santai/sql-shopkeeper')
            ->assertOk()
            ->assertSee('RPL MART')
            ->assertSee('shopkeeper-game')
            ->assertSee('WASD')
            ->assertSee('Terminal SQL')
            ->assertSee('data-initial-state="{&quot;inventory&quot;', false)
            ->assertDontSee('&amp;quot;', false);

        $this->actingAs(User::where('role', 'guru')->firstOrFail())
            ->get('/siswa/zona-santai/sql-shopkeeper')
            ->assertForbidden();
    }

    public function test_select_runs_only_against_shop_dataset_and_level_one_is_server_verified(): void
    {
        $this->actingAs($this->student());
        $this->postJson('/siswa/zona-santai/sql-shopkeeper/query', [
            'level' => 1,
            'submit' => true,
            'query' => 'SELECT nama_barang, stok FROM barang WHERE stok < 5 ORDER BY stok ASC;',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('missionComplete', false)
            ->assertJsonPath('rows.0.nama_barang', 'Air Mineral')
            ->assertJsonPath('rows.2.nama_barang', 'Roti Cokelat');

        $this->postJson('/siswa/zona-santai/sql-shopkeeper/query', [
            'level' => 1,
            'query' => 'SELECT * FROM users',
        ])->assertUnprocessable()->assertJsonPath('ok', false);

        $progress = SqlShopkeeperProgress::firstOrFail();
        $this->assertSame(1, $progress->highest_level);
        $this->assertTrue($progress->game_state['query_passed']['1']);
    }

    public function test_shop_sandbox_rejects_mutations_and_multiple_statements(): void
    {
        $this->actingAs($this->student());
        foreach ([
            'UPDATE barang SET stok = 999',
            'SELECT 1; SELECT 2',
        ] as $query) {
            $this->postJson('/siswa/zona-santai/sql-shopkeeper/query', [
                'level' => 1,
                'query' => $query,
            ])->assertUnprocessable()->assertJsonPath('ok', false);
        }
    }

    public function test_restock_updates_the_saved_store_state_and_level_reward_is_not_repeatable(): void
    {
        $this->actingAs($this->student());
        $url = '/siswa/zona-santai/sql-shopkeeper';
        $this->postJson($url.'/action', ['action' => 'restock', 'item_id' => 1])->assertUnprocessable();
        $this->postJson($url.'/query', [
            'level' => 1,
            'submit' => true,
            'query' => 'SELECT nama_barang, stok FROM barang WHERE stok < 5 ORDER BY stok ASC;',
        ])->assertOk();

        $this->postJson($url.'/action', ['action' => 'restock', 'item_id' => 1])
            ->assertOk()->assertJsonPath('progress.inventory.1', 10);
        $this->postJson($url.'/action', ['action' => 'restock', 'item_id' => 2])
            ->assertOk()->assertJsonPath('missionComplete', true)->assertJsonPath('progress.highestLevel', 2);

        $this->postJson($url.'/query', [
            'level' => 2,
            'submit' => true,
            'query' => 'SELECT barang.nama_barang, SUM(detail_penjualan.jumlah) AS total_terjual FROM detail_penjualan JOIN barang ON barang.id = detail_penjualan.barang_id GROUP BY barang.id, barang.nama_barang ORDER BY total_terjual DESC, barang.nama_barang ASC;',
        ])->assertOk()->assertJsonPath('missionComplete', true);
        $xp = SqlShopkeeperProgress::firstOrFail()->game_xp;

        $this->postJson($url.'/query', [
            'level' => 2,
            'submit' => true,
            'query' => 'SELECT barang.nama_barang, SUM(detail_penjualan.jumlah) AS total_terjual FROM detail_penjualan JOIN barang ON barang.id = detail_penjualan.barang_id GROUP BY barang.id, barang.nama_barang ORDER BY total_terjual DESC, barang.nama_barang ASC;',
        ])->assertOk();

        $this->assertSame($xp, SqlShopkeeperProgress::firstOrFail()->game_xp);
    }

    public function test_served_customer_is_recorded_under_the_correct_customer_in_sales(): void
    {
        $student = $this->student();
        $state = app(SqlShopkeeperGame::class)->initialState();
        SqlShopkeeperProgress::create([
            'student_id' => $student->id,
            'game_state' => $state,
            'highest_level' => 2,
            'game_xp' => 0,
        ]);

        $this->actingAs($student)
            ->postJson('/siswa/zona-santai/sql-shopkeeper/action', ['action' => 'serve_customer'])
            ->assertOk()
            ->assertJsonPath('progress.tables.penjualan.3.nama_pelanggan', 'Naya');
    }
}
