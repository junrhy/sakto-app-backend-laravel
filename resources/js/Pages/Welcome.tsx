import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ApiExample } from '@/Components/ApiExample';
import { useState } from 'react';

type Endpoint = 'list-sales' | 'create-sale' | 'get-sale' | 'list-items' | 'create-item' | 'update-item' | 'delete-item';

export default function Welcome({
    auth,
    laravelVersion,
    phpVersion,
}: PageProps<{ laravelVersion: string; phpVersion: string }>) {
    const [selectedEndpoint, setSelectedEndpoint] = useState<Endpoint>('list-sales');

    return (
        <>
            <Head title="API Documentation" />
            <div className="min-h-screen bg-gray-50 dark:bg-black">
                {/* Navigation */}
                <nav className="bg-white dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700">
                    <div className="w-full px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between h-16">
                            <div className="flex items-center">
                                {/* Logo */}
                                <div className="flex-shrink-0 flex items-center">
                                    <svg
                                        className="h-8 w-auto text-[#FF2D20]"
                                        viewBox="0 0 62 65"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                    >
                                        <path
                                            d="M61.8548 14.6253C61.8778 14.7102 61.8895 14.7978 61.8897 14.8858V28.5615C61.8898 28.737 61.8434 28.9095 61.7554 29.0614C61.6675 29.2132 61.5409 29.3392 61.3887 29.4265L49.9104 36.0351V49.1337C49.9104 49.4902 49.7209 49.8192 49.4118 49.9987L25.4519 63.7916C25.3971 63.8227 25.3372 63.8427 25.2774 63.8639C25.255 63.8714 25.2338 63.8851 25.2101 63.8913C25.0426 63.9354 24.8666 63.9354 24.6991 63.8913C24.6716 63.8838 24.6467 63.8689 24.6205 63.8589C24.5657 63.8389 24.5084 63.8215 24.456 63.7916L0.501061 49.9987C0.348882 49.9113 0.222437 49.7853 0.134469 49.6334C0.0465019 49.4816 0.000120578 49.3092 0 49.1337L0 8.10652C0 8.01678 0.0124642 7.92953 0.0348998 7.84477C0.0423783 7.8161 0.0598282 7.78993 0.0697995 7.76126C0.0884958 7.70891 0.105946 7.65531 0.133367 7.6067C0.152063 7.5743 0.179485 7.54812 0.20192 7.51821C0.230588 7.47832 0.256763 7.43719 0.290416 7.40229C0.319084 7.37362 0.356476 7.35243 0.388883 7.32751C0.425029 7.29759 0.457436 7.26518 0.498568 7.2415L12.4779 0.345059C12.6296 0.257786 12.8015 0.211853 12.9765 0.211853C13.1515 0.211853 13.3234 0.257786 13.475 0.345059L25.4531 7.2415H25.4556C25.4955 7.26643 25.5292 7.29759 25.5653 7.32626C25.5977 7.35119 25.6339 7.37362 25.6625 7.40104C25.6974 7.43719 25.7224 7.47832 25.7523 7.51821C25.7735 7.54812 25.8021 7.5743 25.8196 7.6067C25.8483 7.65656 25.8645 7.70891 25.8844 7.76126C25.8944 7.78993 25.9118 7.8161 25.9193 7.84602C25.9423 7.93096 25.954 8.01853 25.9542 8.10652V33.7317L35.9355 27.9844V14.8846C35.9355 14.7973 35.948 14.7088 35.9704 14.6253C35.9792 14.5954 35.9954 14.5692 36.0053 14.5405C36.0253 14.4882 36.0427 14.4346 36.0702 14.386C36.0888 14.3536 36.1163 14.3274 36.1375 14.2975C36.1674 14.2576 36.1923 14.2165 36.2272 14.1816C36.2559 14.1529 36.292 14.1317 36.3244 14.1068C36.3618 14.0769 36.3942 14.0445 36.4341 14.0208L48.4147 7.12434C48.5663 7.03694 48.7383 6.99094 48.9133 6.99094C49.0883 6.99094 49.2602 7.03694 49.4118 7.12434L61.3899 14.0208C61.4323 14.0457 61.4647 14.0769 61.5021 14.1055C61.5333 14.1305 61.5694 14.1529 61.5981 14.1803C61.633 14.2165 61.6579 14.2576 61.6878 14.2975C61.7103 14.3274 61.7377 14.3536 61.7551 14.386C61.7838 14.4346 61.8 14.4882 61.8199 14.5405C61.8312 14.5692 61.8474 14.5954 61.8548 14.6253Z"
                                            fill="currentColor"
                                        />
                                    </svg>
                                    <span className="ml-2 text-xl font-semibold text-gray-900 dark:text-white">
                                        API Docs
                                    </span>
                                </div>
                            </div>

                            <div className="flex items-center">
                                {auth.user ? (
                                    <Link
                                        href={route('dashboard')}
                                        className="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium"
                                    >
                                        Dashboard
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={route('login')}
                                            className="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium"
                                        >
                                            Log in
                                        </Link>
                                        <Link
                                            href={route('register')}
                                            className="text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-md text-sm font-medium"
                                        >
                                            Register
                                        </Link>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                </nav>

                {/* Main Content */}
                <div className="w-full px-4 sm:px-6 lg:px-8 py-6" style={{ height: 'calc(100vh - 64px - 48px)' }}>
                    <div className="grid grid-cols-12 gap-6 h-full">
                        {/* Sidebar */}
                        <div className="col-span-2 bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
                            <div className="h-full overflow-y-auto">
                                <nav className="p-4 space-y-4">
                                    <div>
                                        <a href="#introduction" className="block px-3 py-2 text-sm font-medium text-gray-900 dark:text-white rounded-md hover:bg-gray-100 dark:hover:bg-zinc-800">
                                            Introduction
                                        </a>
                                    </div>
                                    <div>
                                        <a href="#authentication" className="block px-3 py-2 text-sm font-medium text-gray-900 dark:text-white rounded-md hover:bg-gray-100 dark:hover:bg-zinc-800">
                                            Authentication
                                        </a>
                                    </div>
                                    
                                    {/* Retail Sales Section */}
                                    <div className="space-y-2">
                                        <a href="#retail-sales" className="block px-3 py-2 text-sm font-medium text-gray-900 dark:text-white rounded-md hover:bg-gray-100 dark:hover:bg-zinc-800">
                                            Retail Sales
                                        </a>
                                        <div className="pl-6 space-y-1">
                                            <a 
                                                href="#list-sales" 
                                                className="block px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    setSelectedEndpoint('list-sales');
                                                    document.getElementById('list-sales')?.scrollIntoView({ behavior: 'smooth' });
                                                }}
                                            >
                                                List Sales
                                            </a>
                                            <a 
                                                href="#create-sale"
                                                className="block px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    setSelectedEndpoint('create-sale');
                                                    document.getElementById('create-sale')?.scrollIntoView({ behavior: 'smooth' });
                                                }}
                                            >
                                                Create Sale
                                            </a>
                                            <a 
                                                href="#get-sale"
                                                className="block px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    setSelectedEndpoint('get-sale');
                                                    document.getElementById('get-sale')?.scrollIntoView({ behavior: 'smooth' });
                                                }}
                                            >
                                                Get Sale Details
                                            </a>
                                        </div>
                                    </div>

                                    {/* Inventory Section */}
                                    <div className="space-y-2">
                                        <a href="#inventory" className="block px-3 py-2 text-sm font-medium text-gray-900 dark:text-white rounded-md hover:bg-gray-100 dark:hover:bg-zinc-800">
                                            Inventory
                                        </a>
                                        <div className="pl-6 space-y-1">
                                            <a 
                                                href="#list-items"
                                                className="block px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    setSelectedEndpoint('list-items');
                                                    document.getElementById('list-items')?.scrollIntoView({ behavior: 'smooth' });
                                                }}
                                            >
                                                List Items
                                            </a>
                                            <a 
                                                href="#create-item"
                                                className="block px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    setSelectedEndpoint('create-item');
                                                    document.getElementById('create-item')?.scrollIntoView({ behavior: 'smooth' });
                                                }}
                                            >
                                                Create Item
                                            </a>
                                            <a 
                                                href="#update-item"
                                                className="block px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    setSelectedEndpoint('update-item');
                                                    document.getElementById('update-item')?.scrollIntoView({ behavior: 'smooth' });
                                                }}
                                            >
                                                Update Item
                                            </a>
                                            <a 
                                                href="#delete-item"
                                                className="block px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    setSelectedEndpoint('delete-item');
                                                    document.getElementById('delete-item')?.scrollIntoView({ behavior: 'smooth' });
                                                }}
                                            >
                                                Delete Item
                                            </a>
                                        </div>
                                    </div>
                                </nav>
                            </div>
                        </div>

                        {/* Main content */}
                        <div className="col-span-6 bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
                            <div className="h-full overflow-y-auto">
                                <div className="p-6">
                                    <div className="prose dark:prose-invert max-w-none">
                                        <section id="introduction">
                                            <h1>API Documentation</h1>
                                            <p>
                                                Welcome to the API documentation. This API provides endpoints
                                                for managing retail sales, inventory, and other related
                                                functionalities.
                                            </p>
                                        </section>

                                        <section id="authentication" className="mt-12">
                                            <h2>Authentication</h2>
                                            <p>
                                                All API endpoints require authentication using Bearer tokens.
                                                Include the token in the Authorization header of your requests.
                                            </p>
                                            <div className="bg-gray-100 dark:bg-zinc-800 p-4 rounded-md mt-4">
                                                <code>
                                                    Authorization: Bearer your-token-here
                                                </code>
                                            </div>
                                        </section>

                                        {/* Retail Sales Section */}
                                        <section id="retail-sales" className="mt-12">
                                            <h2>Retail Sales</h2>
                                            
                                            <div id="list-sales" className="mt-6">
                                                <h3>List Sales</h3>
                                                <div className="bg-gray-100 dark:bg-zinc-800 p-4 rounded-md mt-4">
                                                    <h4 className="text-[#FF2D20] font-semibold">GET /api/retail-sales</h4>
                                                    <p className="mt-2">Retrieve a list of retail sales with optional filtering and pagination</p>
                                                </div>
                                            </div>

                                            <div id="create-sale" className="mt-6">
                                                <h3>Create Sale</h3>
                                                <div className="bg-gray-100 dark:bg-zinc-800 p-4 rounded-md mt-4">
                                                    <h4 className="text-[#FF2D20] font-semibold">POST /api/retail-sales</h4>
                                                    <p className="mt-2">Create a new retail sale transaction</p>
                                                </div>
                                            </div>

                                            <div id="get-sale" className="mt-6">
                                                <h3>Get Sale Details</h3>
                                                <div className="bg-gray-100 dark:bg-zinc-800 p-4 rounded-md mt-4">
                                                    <h4 className="text-[#FF2D20] font-semibold">GET /api/retail-sales/{'{id}'}</h4>
                                                    <p className="mt-2">Retrieve detailed information about a specific sale</p>
                                                </div>
                                            </div>
                                        </section>

                                        {/* Inventory Section */}
                                        <section id="inventory" className="mt-12">
                                            <h2>Inventory</h2>

                                            <div id="list-items" className="mt-6">
                                                <h3>List Items</h3>
                                                <div className="bg-gray-100 dark:bg-zinc-800 p-4 rounded-md mt-4">
                                                    <h4 className="text-[#FF2D20] font-semibold">GET /api/inventory</h4>
                                                    <p className="mt-2">Retrieve a list of inventory items with optional filtering and pagination</p>
                                                </div>
                                            </div>

                                            <div id="create-item" className="mt-6">
                                                <h3>Create Item</h3>
                                                <div className="bg-gray-100 dark:bg-zinc-800 p-4 rounded-md mt-4">
                                                    <h4 className="text-[#FF2D20] font-semibold">POST /api/inventory</h4>
                                                    <p className="mt-2">Add a new item to inventory</p>
                                                </div>
                                            </div>

                                            <div id="update-item" className="mt-6">
                                                <h3>Update Item</h3>
                                                <div className="bg-gray-100 dark:bg-zinc-800 p-4 rounded-md mt-4">
                                                    <h4 className="text-[#FF2D20] font-semibold">PUT /api/inventory/{'{id}'}</h4>
                                                    <p className="mt-2">Update an existing inventory item</p>
                                                </div>
                                            </div>

                                            <div id="delete-item" className="mt-6">
                                                <h3>Delete Item</h3>
                                                <div className="bg-gray-100 dark:bg-zinc-800 p-4 rounded-md mt-4">
                                                    <h4 className="text-[#FF2D20] font-semibold">DELETE /api/inventory/{'{id}'}</h4>
                                                    <p className="mt-2">Remove an item from inventory</p>
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Sample Request/Response Panel */}
                        <div className="col-span-4 bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
                            <div className="h-full overflow-y-auto">
                                <div className="p-6">
                                    <ApiExample endpoint={selectedEndpoint} />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Footer */}
                <footer className="bg-white dark:bg-zinc-800 border-t border-gray-200 dark:border-zinc-700 h-12">
                    <div className="w-full px-4 sm:px-6 lg:px-8 h-full flex items-center justify-center">
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Laravel v{laravelVersion} (PHP v{phpVersion})
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
