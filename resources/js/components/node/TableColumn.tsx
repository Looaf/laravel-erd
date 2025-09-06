import { Position } from "reactflow";
import { Handle } from "reactflow";
import { Column, Table } from "../../types/erd";
import { getColumnIcon } from "../../utils";

export interface TableColumnProps {
  table: Table;
  column: Column;
}

export const TableColumn: React.FC<TableColumnProps> = ({ table, column }) => {
  return (
    <div
      key={`${table.id}-${column.name}`}
      style={{
        padding: '2px 4px',
        borderBottom: '1px solid #eee',
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        backgroundColor: column.primary ? '#eff6ff' : 'transparent',
        borderLeft: column.primary ? '3px solid #3b82f6' : 'none'
      }}
    >
      {/* Handles for connections */}
      <Handle type="target" position={Position.Left} />
      <Handle type="source" position={Position.Right} />

      <span style={{ 
        fontWeight: column.primary ? 'bold' : 'normal',
        fontSize: '11px',
        display: 'flex',
        alignItems: 'center',
        gap: '4px',
        flex: 1,
        minWidth: 0,
        overflow: 'hidden',
        textOverflow: 'ellipsis',
        whiteSpace: 'nowrap'
      }}>
        <span style={{ width: '16px', textAlign: 'center', flexShrink: 0 }} title={
          column.primary ? 'Primary Key' : 
          column.unique ? 'Unique' : 
          !column.nullable ? 'Not Nullable' : ''
        }>
          {getColumnIcon(column)}
        </span>
        {column.name}
      </span>
      <span style={{ 
        color: '#666', 
        fontSize: '10px',
        fontFamily: 'monospace',
        flexShrink: 0,
        marginLeft: '8px'
      }}>
        {column.type}
        {column.nullable && <span style={{ color: '#999' }} title="Nullable">?</span>}
      </span>
    </div>
  );
};