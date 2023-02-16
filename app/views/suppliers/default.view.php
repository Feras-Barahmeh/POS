<?php

use APP\Helpers\Structures\Structures;

?>
<h1 class="title-header"><?= $title_header ?></h1>

<div class="header-option flex mt-10">
    <a href="/suppliers/add" class="add-supplier"><?= $text_content_add_supplier ?> <i class="fa fa-plus ml-10"></i></a>
    <div class="search">
        <label for="find-supplier"></label><input type="search" id="find-supplier"  placeholder="<?= $text_content_search_supplier ?>">
        <i class="fa fa-search lens"></i>
    </div>
</div>

<!-- Show Employees -->
<div class="container-table responsive-table mt-20" id="employees-table">
    <table class="">
        <thead>
        <tr>
            <th><?= $text_table_name_supplier ?></th>
            <th><?= $text_table_email_supplier ?></th>
            <th><?= $text_table_phone_number_supplier ?></th>
            <th><?= $text_table_control ?></th>
        </tr>
        </thead>

        <tbody>

                <?php
                    if (! empty($suppliers)) {
                        foreach ($suppliers as $supplier) {
                            ?>
                                <tr class="row-each-employee">
                                    <td class="name-supplier-row"><?= $supplier->Name ?></td>
                                    <td><?= $supplier->Email ?></td>
                                    <td><?= $supplier->PhoneNumber ?></td>

                                    <td class="controller-btns">
                                        <a href="/suppliers/edit/<?= $supplier->SupplierId ?>"><i class="fas fa-edit" aria-hidden="true"></i></a>
                                        <a class="hidden" href="/suppliers/delete/<?= $supplier->SupplierId ?>" id="delete"></a>
                                        <?php Structures::popup(
                                            "you wont delete supplier",
                                            '!', $typeStyle="danger",
                                            $typeAction="link",
                                            $id="delete-supplier"); ?>
                                        <span class="pop-on-click danger-style cursor-pointer"><i class="fa fa-trash" aria-hidden="true"></i></span>

                                    </td>

                                </tr>
                            <?php
                        }
                    }

                ?>
        </tbody>
    </table>
</div>