import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ApiExample } from '@/Components/ApiExample';
import { useState } from 'react';
import ApplicationLogo from '@/Components/ApplicationLogo';

type Endpoint = 'list-sales' | 'create-sale' | 'get-sale' | 'list-items' | 'create-item' | 'update-item' | 'delete-item';

export default function Welcome({
    auth,
    laravelVersion,
    phpVersion,
}: PageProps<{ laravelVersion: string; phpVersion: string }>) {
    const [selectedEndpoint, setSelectedEndpoint] = useState<Endpoint>('list-sales');
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [showExamples, setShowExamples] = useState(false);

    return (
        <>
            <Head title="API Documentation" />
            <div className="min-h-screen bg-gray-50 dark:bg-black">
                {/* Navigation */}
                <nav className="bg-white dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700">
                    <div className="w-full px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between h-16">
                            <div className="flex items-center">
                                {/* Mobile menu button */}
                                <button
                                    onClick={() => setSidebarOpen(!sidebarOpen)}
                                    className="lg:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#FF2D20]"
                                >
                                    <span className="sr-only">Open main menu</span>
                                    {/* Menu icon */}
                                    <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </button>
                                {/* Logo */}
                                <div className="flex-shrink-0 flex items-center ml-4 lg:ml-0">
                                    <ApplicationLogo className="block h-9 w-auto" />
                                    <span className="ml-2 text-xl font-semibold text-gray-900 dark:text-white">
                                        API Docs
                                    </span>
                                </div>
                            </div>

                            {/* Example toggle button for mobile */}
                            <div className="flex items-center lg:hidden">
                                <button
                                    onClick={() => setShowExamples(!showExamples)}
                                    className="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#FF2D20]"
                                >
                                    <span className="sr-only">Toggle examples</span>
                                    <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                    </svg>
                                </button>
                            </div>

                            <div className="hidden lg:flex items-center">
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

                {/* Mobile sidebar */}
                <div className={`fixed inset-0 flex z-40 lg:hidden ${sidebarOpen ? '' : 'hidden'}`}>
                    <div className="fixed inset-0 bg-gray-600 bg-opacity-75" onClick={() => setSidebarOpen(false)}></div>
                    <div className="relative flex-1 flex flex-col max-w-xs w-full bg-white dark:bg-zinc-900">
                        <div className="absolute top-0 right-0 -mr-12 pt-2">
                            <button
                                onClick={() => setSidebarOpen(false)}
                                className="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                            >
                                <span className="sr-only">Close sidebar</span>
                                <svg className="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div className="flex-1 h-0 pt-5 pb-4 overflow-y-auto">
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
                </div>

                {/* Main Content */}
                <div className="w-full px-4 sm:px-6 lg:px-8 py-6" style={{ height: 'calc(100vh - 64px - 48px)' }}>
                    <div className="grid lg:grid-cols-12 gap-6 h-full">
                        {/* Sidebar - Hidden on mobile, shown on desktop */}
                        <div className="hidden lg:block lg:col-span-2 bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
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

                        {/* Main content - Full width on mobile, 6 cols on desktop */}
                        <div className="lg:col-span-6 bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
                            <div className="h-full overflow-y-auto">
                                <div className="p-6">
                                    <div className="prose dark:prose-invert max-w-none">
                                        <section id="introduction">
                                            <h1>API Documentation</h1>
                                            <p>
                                                Welcome to the API documentation. This API provides endpoints
                                                for sakto platform modules.
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

                        {/* Sample Request/Response Panel - Conditional render on mobile */}
                        <div className={`${showExamples ? 'fixed inset-0 z-30 lg:relative lg:inset-auto' : 'hidden'} lg:block lg:col-span-4 bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden`}>
                            <div className="h-full overflow-y-auto">
                                {/* Mobile close button */}
                                <div className="lg:hidden flex justify-end p-2">
                                    <button
                                        onClick={() => setShowExamples(false)}
                                        className="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-[#FF2D20]"
                                    >
                                        <span className="sr-only">Close examples</span>
                                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
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
