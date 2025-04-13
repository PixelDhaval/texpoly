<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared("
           -- Function: insert_packinglist_on_new_customer
            CREATE OR REPLACE FUNCTION insert_packinglist_on_new_customer()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO packinglists (customer_id, product_id, label_name, customer_qty, unit, price, quantity, weight, is_bold, stock)
                SELECT NEW.id, p.id, p.label_name, 0, p.unit, p.price, p.quantity, p.weight, false, 0
                FROM products p;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER insert_packinglist_on_new_customer
            AFTER INSERT ON customers
            FOR EACH ROW
            EXECUTE FUNCTION insert_packinglist_on_new_customer();

            -- Function: insert_packinglist_on_new_product
            CREATE OR REPLACE FUNCTION insert_packinglist_on_new_product()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO packinglists (customer_id, product_id, label_name, customer_qty, unit, price, quantity, weight, is_bold, stock)
                SELECT c.id, NEW.id, NEW.label_name, 0, NEW.unit, NEW.price, NEW.quantity, NEW.weight, false, 0
                FROM customers c;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER insert_packinglist_on_new_product
            AFTER INSERT ON products
            FOR EACH ROW
            EXECUTE FUNCTION insert_packinglist_on_new_product();

            -- Function: insert_orderlist_on_new_packinglist
            CREATE OR REPLACE FUNCTION insert_orderlist_on_new_packinglist()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO orderlists (order_id, packinglist_id, dispatch_qty)
                SELECT orders.id, NEW.id, 0
                FROM orders
                WHERE orders.status IN ('production', 'draft') AND orders.customer_id = NEW.customer_id;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER insert_orderlist_on_new_packinglist
            AFTER INSERT ON packinglists
            FOR EACH ROW
            EXECUTE FUNCTION insert_orderlist_on_new_packinglist();

            -- Function: update_packinglist_on_update_orderlist
            CREATE OR REPLACE FUNCTION update_packinglist_on_update_orderlist()
            RETURNS TRIGGER AS $$
            BEGIN
                UPDATE packinglists
                SET stock = stock + OLD.dispatch_qty - NEW.dispatch_qty
                WHERE id = NEW.packinglist_id;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER update_packinglist_on_update_orderlist
            AFTER UPDATE ON orderlists
            FOR EACH ROW
            EXECUTE FUNCTION update_packinglist_on_update_orderlist();

            -- Function: update_packinglist_on_new_bale
            CREATE OR REPLACE FUNCTION update_packinglist_on_new_bale()
            RETURNS TRIGGER AS $$
            DECLARE
                ref_packinglist_id INT;
            BEGIN
                IF NEW.type IN ('production', 'inward') THEN
                    UPDATE packinglists
                    SET stock = stock + 1
                    WHERE id = NEW.packinglist_id;

                ELSIF NEW.type IN ('outward', 'cutting') THEN
                    UPDATE packinglists
                    SET stock = stock - 1
                    WHERE id = NEW.packinglist_id;

                ELSIF NEW.type = 'repacking' THEN
                    UPDATE packinglists
                    SET stock = stock + 1
                    WHERE id = NEW.packinglist_id;

                    IF NEW.ref_packinglist_id IS NOT NULL THEN
                        UPDATE packinglists
                        SET stock = stock - 1
                        WHERE id = NEW.ref_packinglist_id;
                    END IF;
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER update_packinglist_on_new_bale
            AFTER INSERT ON bales
            FOR EACH ROW
            EXECUTE FUNCTION update_packinglist_on_new_bale();

            -- Function: update_packinglist_on_delete_bale
            CREATE OR REPLACE FUNCTION update_packinglist_on_delete_bale()
            RETURNS TRIGGER AS $$
            BEGIN
                -- Handle cancel bales logging first
                IF OLD.type IN ('production', 'repacking') THEN
                    INSERT INTO cancel_bales (
                        bale_no, packinglist_id, qc, finalist, type,
                        ref_bale_id, ref_packinglist_id, created_at
                    )
                    VALUES (
                        OLD.bale_no, OLD.packinglist_id, OLD.qc, OLD.finalist,
                        OLD.type, OLD.ref_bale_id, OLD.ref_packinglist_id, OLD.created_at
                    );
                END IF;

                -- Handle stock updates based on type
                IF OLD.type = 'repacking' THEN
                    -- For repacking, update both packinglist_id and ref_packinglist_id
                    UPDATE packinglists
                    SET stock = stock - 1
                    WHERE id = OLD.packinglist_id;

                    UPDATE packinglists
                    SET stock = stock + 1
                    WHERE id = OLD.ref_packinglist_id;

                ELSIF OLD.type IN ('production', 'inward') THEN
                    UPDATE packinglists
                    SET stock = stock - 1
                    WHERE id = OLD.packinglist_id;

                ELSIF OLD.type IN ('outward', 'cutting') THEN
                    UPDATE packinglists
                    SET stock = stock + 1
                    WHERE id = OLD.packinglist_id;
                END IF;

                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER update_packinglist_on_delete_bale
            AFTER DELETE ON bales
            FOR EACH ROW
            EXECUTE FUNCTION update_packinglist_on_delete_bale();

            CREATE OR REPLACE FUNCTION create_orderlist_on_new_order()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO orderlists (order_id, packinglist_id, dispatch_qty)
                SELECT NEW.id, p.id, 0
                FROM packinglists p
                WHERE p.customer_id = NEW.customer_id;
                RETURN NEW;
                
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER create_orderlist_on_new_order
            AFTER INSERT ON orders
            FOR EACH ROW
            EXECUTE FUNCTION create_orderlist_on_new_order();
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("
        -- Drop triggers first
        DROP TRIGGER IF EXISTS insert_packinglist_on_new_customer ON customers;
        DROP TRIGGER IF EXISTS insert_packinglist_on_new_product ON products;
        DROP TRIGGER IF EXISTS insert_orderlist_on_new_packinglist ON packinglists;
        DROP TRIGGER IF EXISTS update_packinglist_on_update_orderlist ON orderlists;
        DROP TRIGGER IF EXISTS update_packinglist_on_new_bale ON bales;
        DROP TRIGGER IF EXISTS update_packinglist_on_delete_bale ON bales;
        DROP TRIGGER IF EXISTS create_orderlist_on_new_order ON orders;

        -- Drop functions after triggers
        DROP FUNCTION IF EXISTS insert_packinglist_on_new_customer();
        DROP FUNCTION IF EXISTS insert_packinglist_on_new_product();
        DROP FUNCTION IF EXISTS insert_orderlist_on_new_packinglist();
        DROP FUNCTION IF EXISTS update_packinglist_on_update_orderlist();
        DROP FUNCTION IF EXISTS update_packinglist_on_new_bale();
        DROP FUNCTION IF EXISTS update_packinglist_on_delete_bale();
        DROP FUNCTION IF EXISTS create_orderlist_on_new_order();
    ");
    }
};
