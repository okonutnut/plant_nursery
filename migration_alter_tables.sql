-- Migration: Add SupplierID to user table and PaymentMethod to order table
-- Run this against the plant_nursery database if re-importing from plant_nursery.sql

ALTER TABLE `user`
ADD COLUMN `SupplierID` int NULL DEFAULT NULL AFTER `EmployeeID`,
ADD INDEX `SupplierID`(`SupplierID` ASC),
ADD CONSTRAINT `user_ibfk_3` FOREIGN KEY (`SupplierID`) REFERENCES `supplier` (`SupplierID`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `order`
ADD COLUMN `PaymentMethod` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `IsSuccessful`;
