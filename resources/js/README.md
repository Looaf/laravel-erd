# ERD Frontend Architecture

This document describes the refactored frontend architecture for the Laravel ERD package.

## Directory Structure

```
resources/js/
├── components/           # React components
│   ├── ErdDiagram.tsx   # Main diagram component (entry point)
│   ├── FlowCanvas.tsx   # React Flow canvas wrapper
│   ├── TableNode.tsx    # Individual table node component
│   ├── RelationshipLine.tsx # Custom edge component
│   ├── ZoomControls.tsx # Custom zoom controls
│   ├── ErdHeader.tsx    # Header with title and controls
│   ├── LoadingState.tsx # Loading state component
│   ├── ErrorState.tsx   # Error state component
│   ├── EmptyState.tsx   # Empty state component
│   └── index.ts         # Component exports
├── hooks/               # Custom React hooks
│   ├── useErdData.ts    # Main data management hook
│   └── index.ts         # Hook exports
├── utils/               # Utility functions
│   ├── erdDataUtils.ts  # ERD data conversion utilities
│   ├── apiUtils.ts      # API communication utilities
│   ├── debugUtils.ts    # Debug and logging utilities
│   └── index.ts         # Utility exports
├── types/               # TypeScript type definitions
│   └── erd.ts          # ERD-related types
└── README.md           # This file
```

## Component Hierarchy

```
ErdDiagram (ReactFlowProvider wrapper)
└── ErdDiagramFlow
    ├── ErdHeader
    ├── FlowCanvas (ReactFlow)
    │   ├── ZoomControls
    │   ├── MiniMap
    │   ├── Background
    │   └── CustomTableNode (wraps TableNode)
    ├── LoadingState
    ├── ErrorState
    └── EmptyState
```

## Key Features

### 1. Modular Architecture
- **Components**: Each UI element is a separate, reusable component
- **Hooks**: Business logic is extracted into custom hooks
- **Utils**: Pure functions for data transformation and API calls
- **Types**: Centralized TypeScript definitions

### 2. Debug System
The debug utilities provide comprehensive logging:
- `debugLog(category, message, data?)` - General logging
- `debugError(category, message, error?)` - Error logging
- `debugTable(category, message, data[])` - Tabular data logging
- `debugApiResponse(response)` - API response analysis
- `debugFlowData(nodes, edges)` - React Flow data analysis

### 3. Data Flow
1. `useErdData` hook manages all data state
2. API calls are handled by `apiUtils`
3. Data transformation is done by `erdDataUtils`
4. Components receive clean, processed data

### 4. Error Handling
- Comprehensive error boundaries
- Detailed error logging with debug utilities
- User-friendly error messages
- Retry mechanisms

## Usage

### Basic Usage
```tsx
import { ErdDiagram } from './components';

function App() {
  return <ErdDiagram />;
}
```

### With Custom Config
```tsx
import { ErdDiagram } from './components';

const config = {
  apiEndpoint: '/custom/erd/data',
  refreshEndpoint: '/custom/erd/refresh',
  csrfToken: 'your-token',
  routes: {
    data: '/custom/erd/data',
    refresh: '/custom/erd/refresh'
  }
};

function App() {
  return <ErdDiagram config={config} />;
}
```

## Debugging

### Console Logs
All debug logs are prefixed with timestamps and categories:
```
[2024-01-01T12:00:00.000Z] [ERD-API] Starting ERD data fetch...
[2024-01-01T12:00:00.100Z] [ERD-CONVERT] Converting tables to nodes
[2024-01-01T12:00:00.200Z] [ERD-FLOW] Generated 5 nodes and 3 edges
```

### Debug Categories
- `API` - API calls and responses
- `CONVERT` - Data conversion operations
- `HOOK` - Hook lifecycle and state changes
- `CANVAS` - React Flow canvas operations
- `DEBUG` - General debugging information

### Browser DevTools
1. Open browser DevTools (F12)
2. Go to Console tab
3. Look for ERD-prefixed logs
4. Use `console.table()` output for structured data

## Troubleshooting

### Common Issues

1. **No data displayed**
   - Check API response in console logs
   - Verify CSRF token is present
   - Check network tab for failed requests

2. **Nodes not draggable**
   - Verify React Flow is properly initialized
   - Check for JavaScript errors in console

3. **Relationships not showing**
   - Check relationship data structure in debug logs
   - Verify source/target node IDs match table IDs

4. **Performance issues**
   - Check number of nodes/edges in debug logs
   - Consider implementing virtualization for large datasets

### Debug Steps
1. Enable verbose logging by checking console
2. Use `debugApiResponse()` to analyze API data
3. Use `debugFlowData()` to verify React Flow conversion
4. Check component render cycles with React DevTools

## Extending

### Adding New Components
1. Create component in `components/` directory
2. Export from `components/index.ts`
3. Add TypeScript types if needed

### Adding New Utilities
1. Create utility file in `utils/` directory
2. Export from `utils/index.ts`
3. Add unit tests if applicable

### Adding New Hooks
1. Create hook in `hooks/` directory
2. Export from `hooks/index.ts`
3. Follow React hooks conventions